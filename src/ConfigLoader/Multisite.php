<?php declare(strict_types=1);

namespace WpWildfire\ConfigLoader;

use WpWildfire\ConfigLoader;
use WpWildfire\ConfigLoaderInterface;

/**
 * WIP - this is the starting point for a comprehensive multi-site configuration
 * handler to simplify the various configurations required for multisite. This
 * is not wired in and is very much only a proof of concept at this point
 */
class Multisite implements ConfigLoaderInterface
{
    public function load(ConfigLoader $loader)
    {
        /* Multisite Configured */
        if (isset($_ENV['DOMAIN_CURRENT_SITE']) && (bool)$_ENV['DOMAIN_CURRENT_SITE']) {
            define('MULTISITE', true);
            define('SUBDOMAIN_INSTALL', isset($_ENV['SUBDOMAIN_INSTALL']) && (bool)$_ENV['SUBDOMAIN_INSTALL']);
            define('DOMAIN_CURRENT_SITE', $_ENV('DOMAIN_CURRENT_SITE'));
            define('PATH_CURRENT_SITE', $_ENV('PATH_CURRENT_SITE'));
            define('SITE_ID_CURRENT_SITE', $_ENV['SITE_ID_CURRENT_SITE']);
            define('BLOG_ID_CURRENT_SITE', $_ENV['BLOG_ID_CURRENT_SITE']);
            define('SUNRISE', TRUE);
            $loader->addCallback(function(){

                // Rewrite multi-site image paths to use main sites images
                \add_filter('wp_get_attachment_url', function($url) {
                    if(strpos($url, 'uploads/sites/2/') !== false) {
                        return str_replace('uploads/sites/2/', 'uploads/sites/1/', $url);
                    } elseif(strpos($url, 'uploads/sites/3/') !== false) {
                        return str_replace('uploads/sites/3/', 'uploads/sites/1/', $url);
                    } elseif(strpos($url, 'uploads/sites/4/') !== false) {
                        return str_replace('uploads/sites/4/', 'uploads/sites/1/', $url);
                    } else {
                        return $url;
                    }
                });

                // Rewrite multi-site image paths to use main sites images in responsive images
                \add_filter( 'wp_calculate_image_srcset', function( $sources )
                {
                    foreach( $sources as &$source )
                    {
                        if( isset( $source['url'] ) ) {
                            if(strpos($source['url'], 'uploads/sites/2/') !== false) {
                                $source['url'] = str_replace('uploads/sites/2/', 'uploads/sites/1/', $source['url']);
                            } elseif(strpos($source['url'], 'uploads/sites/3/') !== false) {
                                $source['url'] = str_replace('uploads/sites/3/', 'uploads/sites/1/', $source['url']);
                            } elseif(strpos($source['url'], 'uploads/sites/4/') !== false) {
                                $source['url'] = str_replace('uploads/sites/4/', 'uploads/sites/1/', $source['url']);
                            }
                        }
                    }
                    return $sources;

                }, PHP_INT_MAX );

            });
        }
    }

}
