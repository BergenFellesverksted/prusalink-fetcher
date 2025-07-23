<?php

/**
 * Shortcode: [printer_cards]
 *
 * Outputs a responsive grid of “cards” showing each printer’s live status,
 * with stale cards (last update >10 min ago) rendered at 50% opacity.
 */
// helper to turn seconds into “1h 2m 3s”
function format_hms( $sec ) {
    $h = intval( floor( $sec / 3600 ) );
    $m = intval( floor( ( $sec % 3600 ) / 60 ) );
    $s = intval( $sec % 60 );
    $parts = [];
    if ( $h > 0 ) {
        $parts[] = $h . 'h';
    }
    if ( $m > 0 || $h > 0 ) {
        $parts[] = $m . 'm';
    }
    $parts[] = $s . 's';
    return implode( ' ', $parts );
}

function render_printer_cards() {
    global $wpdb;

    // 1) table name
    $table = '3dprinter_status';

    // 2) fetch rows
    $rows = $wpdb->get_results(
        "SELECT printer_name, state,
                time_printing, time_remaining,
                temp_bed, target_bed,
                temp_nozzle, target_nozzle,
                axis_z, last_updatedUTC
         FROM `$table`
         ORDER BY printer_name ASC",
        ARRAY_A
    );

    if ( empty( $rows ) ) {
        return '<p>No printer status data found.</p>';
    }

    // 3) build CSS + container
    $html = '
    <style>
      .printer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px,1fr));
        gap: 1rem;
        margin: 1rem 0;
      }
      .printer-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: opacity 0.3s;
      }
      .printer-card h3 {
        margin-top: 0;
        font-size: 1.1em;
        font-weight: bold;
      }
      .printer-card .stat {
        margin: 0.5em 0;
        font-size: 0.75em;
        color: gray;
      }
    </style>

    <div class="printer-grid">';

    // WP date/time formats
    $date_fmt = get_option('date_format');
    $time_fmt = get_option('time_format');
    $now_utc = new DateTime('now', new DateTimeZone('UTC'));

    foreach ( $rows as $r ) {
        // calculate percentage
        $printed   = floatval( $r['time_printing'] );
        $remaining = floatval( $r['time_remaining'] );
        $pct = ($printed + $remaining) > 0
             ? round( $printed / ($printed + $remaining) * 100, 1 )
             : 0;

        // parse last_updatedUTC, compute age
        try {
            $dt_utc = new DateTime( $r['last_updatedUTC'], new DateTimeZone('UTC') );
            $age_seconds = $now_utc->getTimestamp() - $dt_utc->getTimestamp();
        } catch ( Exception $e ) {
            $age_seconds = 0;
        }

        // determine opacity style if stale
        $opacity_style = $age_seconds > 600
                       ? 'style="opacity:0.5;"'
                       : '';

        // convert UTC → Europe/Oslo for display
        try {
            $dt_utc->setTimezone( new DateTimeZone('Europe/Oslo') );
            $last = $dt_utc->format( $date_fmt . ' ' . $time_fmt );
        } catch ( Exception $e ) {
            $last = esc_html( $r['last_updatedUTC'] );
        }

        // render card with conditional opacity
        $html .= "<div class=\"printer-card\" {$opacity_style}>";
        $html .= '<h3>' . esc_html( $r['printer_name'] ) . '</h3>';
        $html .= '<div class="state">State: ' 
               . esc_html( strtoupper( $r['state'] ) ) 
               . "</div>\n";
        $html .= '<div class="percent">Progress: ' . esc_html( $pct ) . "%</div>\n";
        $html .= '<div class="stat">Printing: ' 
              . esc_html( format_hms( $printed ) ) 
              . "</div>\n";
        $html .= '<div class="stat">Remaining: ' 
              . esc_html( format_hms( $remaining ) ) 
              . "</div>\n";
        $html .= '<div class="stat">Bed: ' 
               . number_format_i18n( floatval($r['temp_bed']), 1 )
               . ' / ' 
               . number_format_i18n( floatval($r['target_bed']), 1 )
               . "°C</div>\n";
        $html .= '<div class="stat">Nozzle: ' 
               . number_format_i18n( floatval($r['temp_nozzle']), 1 )
               . ' / ' 
               . number_format_i18n( floatval($r['target_nozzle']), 1 )
               . "°C</div>\n";
        $html .= '<div class="stat">Z: ' 
               . number_format_i18n( floatval($r['axis_z']), 2 )
               . " mm</div>\n";
        $html .= '<div class="stat">Last updated: ' 
               . esc_html( $last ) 
               . "</div>\n";
        $html .= "</div>\n";
    }

    $html .= "</div>\n";
    return $html;
}
add_shortcode( 'printer_cards', 'render_printer_cards' );


?>