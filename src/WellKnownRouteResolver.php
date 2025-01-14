<?php

namespace Didgeridoo;

class WellKnownRouteResolver
{
    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_well_known_request']);
    }

    function add_rewrite_rules()
    {
        add_rewrite_rule('^.well-known/atproto-did?$', 'index.php?well_known_atproto_did=1', 'top');
        add_rewrite_tag('%well_known_atproto_did%', '([^&]+)');
    }

    public function handle_well_known_request()
    {
        if (!get_query_var('well_known_atproto_did')) {
            return;
        }

        $httpHost = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';

        // Check if the handle is valid https://atproto.com/specs/handle#handle-identifier-syntax
        // Don't even bother handling invalid handles
        if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/i', $httpHost)) {
            status_header(400);
            header('Content-Type: text/plain');
            echo "Invalid handle\n";
            exit;
        }

        $didList = get_option('didgeridoo_did_list');
        $didList = json_decode($didList, true);

        // Something is wrong with the stored DID list
        if (!is_array($didList)) {
            status_header(500);
            echo "Internal server error. If you are the owner of this domain, please resave your DID settings.\n";
            exit;
        }

        $handleParts = explode('.', $httpHost);
        $name = $handleParts[0];
        $domain = implode('.', array_slice($handleParts, 1));
        $didgeridooSubdomain = get_option('didgeridoo_subdomain');

        $siteUrl = get_site_url();
        $urlParts = parse_url($siteUrl);
        $siteDomain = $urlParts['host'];

        $userSubdomain = ($didgeridooSubdomain ? $didgeridooSubdomain . '.' : '') . $siteDomain;

        if ($httpHost === $siteDomain) {
            $did = get_option('didgeridoo_main_did');
        } else if ($domain === $userSubdomain) {
            $userSetting = $this->array_find($didList, function($value) use ($name) {
                return $value['name'] === $name;
            });

            $did = $userSetting['did'] ?? false;
        } 

        if (empty($did)) {
            status_header(404);
            header('Content-Type: text/plain');
            echo "Not found\n";
            exit;
        }

        // Output the response as plain text
        header('Content-Type: text/plain');
        echo $did;
        echo "\n";
        exit;
    }

    /**
     * PHP 8.0 has array_find, but we need to support PHP 7.4 for WordPress
     * @param array $array
     * @param callable $callback
     * @return mixed|null
     */
    private function array_find($array, $callback)
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        return null;
    }
}