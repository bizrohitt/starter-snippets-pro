<?php
/**
 * Condition Engine – evaluates whether a snippet should run.
 *
 * @package StarterSnippets\Modules\ConditionEngine
 */

namespace StarterSnippets\Modules\ConditionEngine;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ConditionEngine {

    /**
     * Decide if a snippet should execute given its conditions.
     *
     * If no conditions exist, the snippet runs everywhere it's allowed by location.
     *
     * @param array<object> $conditions  Condition rows from the DB.
     * @return bool
     */
    public function should_run( array $conditions ): bool {
        if ( empty( $conditions ) ) {
            return true;
        }

        // Group conditions by type.
        $groups = [];
        foreach ( $conditions as $cond ) {
            $groups[ $cond->condition_type ][] = $cond;
        }

        /*
         * Logic: all condition *groups* must pass (AND between groups).
         * Within a group, any condition matching is enough (OR within group).
         * Exclude conditions invert the result.
         */
        foreach ( $groups as $type => $conds ) {
            if ( ! $this->evaluate_group( $type, $conds ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a group of conditions of the same type.
     *
     * @param string          $type
     * @param array<object>   $conditions
     */
    private function evaluate_group( string $type, array $conditions ): bool {
        foreach ( $conditions as $cond ) {
            $match   = $this->evaluate_single( $type, $cond->condition_value );
            $include = 'include' === $cond->condition_operator;

            // For include: if it matches, the group passes.
            if ( $include && $match ) {
                return true;
            }

            // For exclude: if it matches, the group fails.
            if ( ! $include && $match ) {
                return false;
            }
        }

        // If only include conditions existed and none matched → fail.
        $has_includes = false;
        foreach ( $conditions as $cond ) {
            if ( 'include' === $cond->condition_operator ) {
                $has_includes = true;
                break;
            }
        }

        // If all were excludes and none matched → pass.
        return ! $has_includes;
    }

    /**
     * Evaluate a single condition against the current request.
     */
    private function evaluate_single( string $type, string $value ): bool {
        $is_pro = \StarterSnippets\Core\starter_snippets_is_pro_active();

        return match ( $type ) {
            'page_id'          => $this->is_page( $value ),
            'post_id'          => $this->is_post( $value ),
            'post_type'        => $this->is_post_type( $value ),
            'user_role'        => $this->has_role( $value ),
            'logged_in'        => $this->is_logged_in( $value ),
            'url_pattern'      => $this->url_matches( $value ),
            'device'           => $is_pro && $this->match_device( $value ),
            'schedule'         => $is_pro && $this->match_schedule( $value ),
            'country'          => $is_pro && $this->match_country( $value ),
            'woo_cart_total'   => $is_pro && $this->match_woo_cart_total( $value ),
            'woo_cart_product' => $is_pro && $this->match_woo_cart_product( $value ),
            default            => false,
        };
    }

    private function is_page( string $id ): bool {
        return is_page( (int) $id );
    }

    private function is_post( string $id ): bool {
        return is_single( (int) $id );
    }

    private function is_post_type( string $type ): bool {
        return is_singular( $type ) || ( is_post_type_archive( $type ) );
    }

    private function has_role( string $role ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array( $role, (array) $user->roles, true );
    }

    private function is_logged_in( string $value ): bool {
        $want_logged_in = in_array( strtolower( $value ), [ '1', 'yes', 'true' ], true );
        return $want_logged_in === is_user_logged_in();
    }

    private function url_matches( string $pattern ): bool {
        $current = $_SERVER['REQUEST_URI'] ?? '';

        // Suppress errors for invalid regex.
        $result = @preg_match( $pattern, $current );
        return 1 === $result;
    }

    private function match_device( string $device ): bool {
        $is_mobile = wp_is_mobile();
        $device    = strtolower( trim( $device ) );

        if ( 'mobile' === $device ) {
            return $is_mobile;
        }
        if ( 'desktop' === $device ) {
            return ! $is_mobile;
        }
        return false;
    }

    private function match_schedule( string $date_range ): bool {
        // Expected format: YYYY-MM-DD:YYYY-MM-DD
        $parts = explode( ':', $date_range );
        if ( count( $parts ) !== 2 ) {
            return false;
        }

        $start = strtotime( trim( $parts[0] ) );
        $end   = strtotime( trim( $parts[1] ) );
        $now   = time() + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

        if ( ! $start || ! $end ) {
            return false;
        }

        // Make end date inclusive by adding 23:59:59 (86399 seconds)
        $end += 86399;

        return $now >= $start && $now <= $end;
    }

    private function match_country( string $country_code ): bool {
        // Use Cloudflare header if available (most reliable server-side method for free).
        $user_country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';
        
        if ( ! $user_country ) {
            // Support WooCommerce GeoIP if CF is not present and woo is active.
            if ( class_exists( 'WC_Geolocation' ) ) {
                $geo = \WC_Geolocation::geolocate_ip();
                $user_country = $geo['country'] ?? '';
            }
        }

        if ( ! $user_country ) {
            return false; // Can't determine
        }

        return strtolower( $user_country ) === strtolower( trim( $country_code ) );
    }

    private function match_woo_cart_total( string $amount ): bool {
        if ( ! class_exists( 'WooCommerce' ) || ! isset( WC()->cart ) ) {
            return false;
        }

        $operator = '>';
        $target   = (float) $amount;

        // Parse optional operator e.g. ">100" or "<=50"
        if ( preg_match( '/^([<>]=?|=)\s*([\d.]+)$/', trim( $amount ), $matches ) ) {
            $operator = $matches[1];
            $target   = (float) $matches[2];
        }

        $total = (float) WC()->cart->get_cart_contents_total();

        return match ( $operator ) {
            '>'  => $total > $target,
            '>=' => $total >= $target,
            '<'  => $total < $target,
            '<=' => $total <= $target,
            '='  => $total === $target,
            default => $total >= $target
        };
    }

    private function match_woo_cart_product( string $product_id ): bool {
        if ( ! class_exists( 'WooCommerce' ) || ! isset( WC()->cart ) ) {
            return false;
        }

        $target_id = (int) trim( $product_id );

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( $cart_item['product_id'] === $target_id || $cart_item['variation_id'] === $target_id ) {
                return true; // Found in cart
            }
        }

        return false;
    }
}
