<?php

namespace App\Services\Backlink;

use App\Models\Backlink;
use App\Services\Security\UrlValidator;
use App\Exceptions\SsrfException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class BacklinkCheckerService
{
    protected UrlValidator $urlValidator;

    // Timeout en secondes pour les requêtes HTTP
    protected int $timeout = 30;

    // User-Agent pour identifier notre bot
    protected string $userAgent = 'LinkTracker-Bot/1.0 (Backlink Monitoring)';

    public function __construct(UrlValidator $urlValidator)
    {
        $this->urlValidator = $urlValidator;
    }

    /**
     * Vérifie un backlink et retourne les résultats de la vérification
     *
     * @param Backlink $backlink
     * @return array
     */
    public function check(Backlink $backlink): array
    {
        $result = [
            'is_present' => false,
            'http_status' => null,
            'anchor_text' => null,
            'rel_attributes' => null,
            'is_dofollow' => false,
            'is_noindex' => null,
            'error_message' => null,
        ];

        try {
            // 1. Validation SSRF
            $this->urlValidator->validate($backlink->source_url);

            // 2. Faire la requête HTTP
            $response = Http::timeout($this->timeout)
                ->withUserAgent($this->userAgent)
                ->get($backlink->source_url);

            $result['http_status'] = $response->status();

            // 3. Vérifier si la requête a réussi
            if (!$response->successful()) {
                $result['error_message'] = "HTTP {$response->status()} - Page non accessible";
                return $result;
            }

            // 4. Analyser le HTML pour trouver le backlink
            $html = $response->body();

            // Détecter noindex dans les meta robots
            $result['is_noindex'] = $this->detectNoindex($html);

            $linkData = $this->findLinkInHtml($html, $backlink->target_url);

            if ($linkData) {
                $result['is_present'] = true;
                $result['anchor_text'] = $linkData['anchor_text'];
                $result['rel_attributes'] = $linkData['rel_attributes'];
                $result['is_dofollow'] = $linkData['is_dofollow'];
            } else {
                $result['error_message'] = 'Backlink non trouvé dans la page';
            }

        } catch (SsrfException $e) {
            $result['error_message'] = 'SSRF Protection: ' . $e->getMessage();
            Log::warning('SSRF attempt blocked during backlink check', [
                'backlink_id' => $backlink->id,
                'source_url' => $backlink->source_url,
            ]);
        } catch (\Exception $e) {
            $result['error_message'] = 'Erreur: ' . $e->getMessage();
            Log::error('Backlink check failed', [
                'backlink_id' => $backlink->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Cherche un lien vers target_url dans le HTML
     *
     * @param string $html
     * @param string $targetUrl
     * @return array|null
     */
    protected function findLinkInHtml(string $html, string $targetUrl): ?array
    {
        // Supprimer les erreurs HTML pour éviter les warnings DOM
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        // Charger le HTML (avec gestion d'encodage)
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        if (!@$dom->loadHTML($html)) {
            libxml_clear_errors();
            return null;
        }

        $xpath = new DOMXPath($dom);

        // Trouver tous les liens <a> dans la page
        $links = $xpath->query('//a[@href]');

        if (!$links) {
            libxml_clear_errors();
            return null;
        }

        // Parcourir tous les liens pour trouver celui qui pointe vers target_url
        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            // Normaliser les URLs pour la comparaison
            if ($this->urlsMatch($href, $targetUrl)) {
                $anchorText = trim($link->textContent);
                $rel = $link->getAttribute('rel');

                $relAttributes = $rel ? array_map('trim', explode(' ', strtolower($rel))) : [];
                $isDofollow = !in_array('nofollow', $relAttributes);

                libxml_clear_errors();

                return [
                    'anchor_text' => $anchorText ?: null,
                    'rel_attributes' => !empty($relAttributes) ? implode(',', $relAttributes) : null,
                    'is_dofollow' => $isDofollow,
                ];
            }
        }

        libxml_clear_errors();
        return null;
    }

    /**
     * Détecte si la page contient une directive noindex dans les meta robots
     * Retourne true si noindex détecté, false si index explicite, null si aucune meta robots
     */
    protected function detectNoindex(string $html): ?bool
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        if (!@$dom->loadHTML($html)) {
            libxml_clear_errors();
            return null;
        }

        $xpath = new DOMXPath($dom);

        // Cherche <meta name="robots" content="..."> (et variantes googlebot, etc.)
        $metaNodes = $xpath->query('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="robots" or translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="googlebot"]');

        libxml_clear_errors();

        if (!$metaNodes || $metaNodes->length === 0) {
            return null; // Pas de meta robots = on ne sait pas
        }

        foreach ($metaNodes as $meta) {
            $content = strtolower($meta->getAttribute('content'));
            if (str_contains($content, 'noindex')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare deux URLs pour vérifier si elles correspondent
     * Gère les variations (http/https, trailing slash, www, etc.)
     *
     * @param string $url1
     * @param string $url2
     * @return bool
     */
    protected function urlsMatch(string $url1, string $url2): bool
    {
        // Normaliser les URLs (ignore protocole)
        $normalized1 = $this->normalizeUrl($url1);
        $normalized2 = $this->normalizeUrl($url2);

        // Retirer le protocole pour la comparaison (http:// ou https://)
        $normalized1WithoutProtocol = preg_replace('/^https?:\/\//', '', $normalized1);
        $normalized2WithoutProtocol = preg_replace('/^https?:\/\//', '', $normalized2);

        // Comparaison exacte sans protocole
        if ($normalized1WithoutProtocol === $normalized2WithoutProtocol) {
            return true;
        }

        // Comparaison avec/sans www
        $normalized1WithoutWww = preg_replace('/^www\./', '', $normalized1WithoutProtocol);
        $normalized2WithoutWww = preg_replace('/^www\./', '', $normalized2WithoutProtocol);

        return $normalized1WithoutWww === $normalized2WithoutWww;
    }

    /**
     * Normalise une URL pour la comparaison
     *
     * @param string $url
     * @return string
     */
    protected function normalizeUrl(string $url): string
    {
        // Convertir en URL absolue si relative
        if (!preg_match('/^https?:\/\//', $url)) {
            return $url; // Retourner tel quel si ce n'est pas une URL complète
        }

        try {
            $parsed = parse_url(strtolower($url));

            if (!$parsed) {
                return $url;
            }

            // Construire l'URL normalisée
            $scheme = $parsed['scheme'] ?? 'http';
            $host = $parsed['host'] ?? '';
            $path = $parsed['path'] ?? '/';

            // Retirer le trailing slash sauf pour la racine
            if ($path !== '/' && str_ends_with($path, '/')) {
                $path = rtrim($path, '/');
            }

            $normalized = "{$scheme}://{$host}{$path}";

            // Ajouter query string si présente
            if (isset($parsed['query'])) {
                $normalized .= '?' . $parsed['query'];
            }

            return $normalized;

        } catch (\Exception $e) {
            return $url;
        }
    }

    /**
     * Configure le timeout pour les requêtes HTTP
     *
     * @param int $timeout
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Configure le User-Agent
     *
     * @param string $userAgent
     * @return self
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }
}
