<?php
/**
 * Shortcode: [printer_cards]
 *
 * Outputs a responsive grid of “cards” showing each printer’s live status,
 * with stale cards (last update >10 min ago) rendered at 50% opacity,
 * auto-refreshing every minute via AJAX, and showing an ETA (HH:MM Oslo time)
 * calculated from the last_updatedUTC timestamp.
 */

/**
 * Helper: turn seconds into “1h 2m 3s” but only include non‑zero components.
 */
function format_hms( $sec ) {
    $h = intval( floor( $sec / 3600 ) );
    $m = intval( floor( ( $sec % 3600 ) / 60 ) );
    $s = intval( $sec % 60 );
    $parts = [];
    if ( $h > 0 ) {
        $parts[] = $h . 'h';
    }
    if ( $m > 0 ) {
        $parts[] = $m . 'm';
    }
    if ( $s > 0 ) {
        $parts[] = $s . 's';
    }
    if ( empty( $parts ) ) {
        return '0s';
    }
    return implode( ' ', $parts );
}

/**
 * AJAX handler (public & private) to return the cards HTML.
 */
add_action( 'wp_ajax_nopriv_get_printer_cards', 'ajax_printer_cards' );
add_action( 'wp_ajax_get_printer_cards',        'ajax_printer_cards' );
function ajax_printer_cards() {
    echo render_printer_cards();
    wp_die();
}

/**
 * Main shortcode callback.
 */
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

    // Prepare for time calculations
    $date_fmt = get_option('date_format');
    $time_fmt = get_option('time_format');

    ob_start();

    // Only include CSS on initial page load
    if ( ! defined( 'DOING_AJAX' ) ) : ?>
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
    <?php endif; ?>

    <div id="printer-cards-container" class="printer-grid">
    <?php foreach ( $rows as $r ) :

        // Raw timestamps
        try {
            $dt_last = new DateTime( $r['last_updatedUTC'], new DateTimeZone('UTC') );
        } catch ( Exception $e ) {
            continue;
        }

        // Calculate percent complete
        $printed   = floatval( $r['time_printing'] );
        $remaining = floatval( $r['time_remaining'] );
        $pct = ( $printed + $remaining ) > 0
             ? round( $printed / ( $printed + $remaining ) * 100, 1 )
             : 0;

        // Compute age for opacity check
        $now_utc = new DateTime('now', new DateTimeZone('UTC'));
        $age_seconds = $now_utc->getTimestamp() - $dt_last->getTimestamp();
        $opacity_attr = $age_seconds > 600
                      ? ' style="opacity:0.5;"'
                      : '';

        // Compute ETA from last_updatedUTC
        $eta_str = '';
        if ( $remaining > 0 ) {
            $eta = clone $dt_last;
            $eta->modify( '+' . intval( $remaining ) . ' seconds' );
            $eta->setTimezone( new DateTimeZone('Europe/Oslo') );
            $eta_str = $eta->format('H:i');
        }

        // Format last updated for display
        $dt_last->setTimezone( new DateTimeZone('Europe/Oslo') );
        $last_display = $dt_last->format( $date_fmt . ' ' . $time_fmt );
    ?>
      <div class="printer-card"<?php echo $opacity_attr; ?>>
        <h3><?php echo esc_html( $r['printer_name'] ); ?></h3>
        <div class="state">State: <?php echo esc_html( strtoupper( $r['state'] ) ); ?></div>
        <div class="percent">Progress: <?php echo esc_html( $pct ); ?>%</div>
        <div class="stat">Printing: <?php echo esc_html( format_hms( $printed ) ); ?></div>
        <div class="stat">
          Remaining: <?php echo esc_html( format_hms( $remaining ) ); ?>
          <?php if ( $eta_str ) : ?> / ETA: <?php echo esc_html( $eta_str ); ?><?php endif; ?>
        </div>
        <div class="stat">Bed:
          <?php echo esc_html( number_format_i18n( floatval($r['temp_bed']), 1 ) ); ?> /
          <?php echo esc_html( number_format_i18n( floatval($r['target_bed']), 1 ) ); ?>°C
        </div>
        <div class="stat">Nozzle:
          <?php echo esc_html( number_format_i18n( floatval($r['temp_nozzle']), 1 ) ); ?> /
          <?php echo esc_html( number_format_i18n( floatval($r['target_nozzle']), 1 ) ); ?>°C
        </div>
        <div class="stat">Z: <?php echo esc_html( number_format_i18n( floatval($r['axis_z']), 2 ) ); ?> mm</div>
        <div class="stat">Last updated: <?php echo esc_html( $last_display ); ?></div>
      </div>
    <?php endforeach; ?>
    </div>

    <?php if ( ! defined( 'DOING_AJAX' ) ) : ?>
    <script>
    (function(){
      var containerID = 'printer-cards-container';
      var ajaxURL     = '<?php echo esc_js( admin_url('admin-ajax.php') . '?action=get_printer_cards' ); ?>';
      function refresh() {
        fetch( ajaxURL )
          .then( res => res.text() )
          .then( html => {
            var old = document.getElementById(containerID);
            if ( old && html ) {
              var temp = document.createElement('div');
              temp.innerHTML = html;
              old.parentNode.replaceChild(temp.firstElementChild, old);
            }
          });
      }
      setInterval( refresh, 60000 );
    })();
    </script>
    <?php endif;

    return ob_get_clean();
}
add_shortcode( 'printer_cards', 'render_printer_cards' );
