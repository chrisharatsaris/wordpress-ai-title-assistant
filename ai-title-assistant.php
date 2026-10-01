<?php
/**
 * Plugin Name: AI Content Title Assistant
 * Plugin URI:  https://example.com/ai-content-title-assistant
 * Description: Ένα εργαλείο διαχείρισης για τη δημιουργία SEO τίτλων μέσω προσομοιωμένου AI, σχεδιασμένο για το WordPress Dashboard.
 * Version:     1.0.0
 * Author:      Senior WordPress Developer
 * Author URI:  https://example.com
 * License:     GPLv2 or later
 * Text Domain: ai-content-title-assistant
 */

// Αποτροπή άμεσης πρόσβασης στο αρχείο για λόγους ασφαλείας.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Προσθήκη του μενού στο WordPress Admin Dashboard.
 * Χρησιμοποιούμε το hook 'admin_menu' και περιορίζουμε την πρόσβαση σε διαχειριστές.
 */
function aicta_register_admin_menu() {
    add_management_page(
        __( 'AI Content Title Assistant', 'ai-content-title-assistant' ),
        __( 'AI Title Assistant', 'ai-content-title-assistant' ),
        'manage_options', // Security: Έλεγχος capability - μόνο Administrators
        'ai-content-title-assistant',
        'aicta_render_admin_page'
    );
}
add_action( 'admin_menu', 'aicta_register_admin_menu' );

/**
 * 2. Προσομοίωση AI (Mock Response)
 * Δέχεται το θέμα και επιστρέφει 3 τυχαίους SEO τίτλους στα ελληνικά.
 */
function aicta_mock_ai_generator( $topic ) {
    $clean_topic = sanitize_text_field( $topic );
    
    // Τράπεζα δεδομένων για την προσομοίωση
    $templates = array(
        "Ο απόλυτος οδηγός για: %s (Τι πρέπει να ξέρεις)",
        "10 Κορυφαία μυστικά σχετικά με %s που αγνοείς",
        "Πώς να πετύχεις τα πάντα σε σχέση με %s φέτος",
        "Οδηγός επιβίωσης: Όλα όσα αφορούν το %s",
        "Γιατί το %s είναι η μεγαλύτερη τάση τώρα"
    );

    // Τυχαία επιλογή 3 διαφορετικών προτάσεων
    shuffle( $templates );
    $selected = array_slice( $templates, 0, 3 );

    $titles = array();
    foreach ( $selected as $template ) {
        $titles[] = sprintf( $template, $clean_topic );
    }

    return $titles;
}

/**
 * 3. Δ1αχείριση και εμφάνιση της Σελίδας Διαχείρισης (UI & Form Handling)
 */
function aicta_render_admin_page() {
    // Security: Διπλός έλεγχος capabilities για την εκτέλεση της σελίδας
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Δεν έχετε τα απαραίτητα δικαιώματα για πρόσβαση σε αυτή τη σελίδα.', 'ai-content-title-assistant' ) );
    }

    $generated_titles = array();
    $user_topic = '';

    // Έλεγχος αν έχει γίνει υποβολή της φόρμας
    if ( isset( $_POST['aicta_generate_nonce'] ) ) {
        
        // Security: Επιβεβαίωση Nonce για προστασία από επιθέσεις CSRF
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aicta_generate_nonce'] ) ), 'aicta_action_nonce' ) ) {
            wp_die( esc_html__( 'Η επαλήθευση ασφαλείας απέτυχε. Παρακαλώ δοκιμάστε ξανά.', 'ai-content-title-assistant' ) );
        }

        // Security: Sanitization του input πριν την επεξεργασία
        if ( isset( $_POST['aicta_topic'] ) ) {
            $user_topic = sanitize_text_field( wp_unslash( $_POST['aicta_topic'] ) );
            if ( ! empty( $user_topic ) ) {
                $generated_titles = aicta_mock_ai_generator( $user_topic );
            }
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php esc_html_e( 'Εισάγετε το θέμα ή τη λέξη-κλειδί παρακάτω για να δημιουργήσετε προσομοιωμένους SEO τίτλους.', 'ai-content-title-assistant' ); ?></p>
        
        <form method="post" action="">
            <?php 
                // Security: Δημιουργία κρυφού πεδίου Nonce για ασφαλή μεταφορά δεδομένων
                wp_nonce_field( 'aicta_action_nonce', 'aicta_generate_nonce' ); 
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="aicta_topic"><?php esc_html_e( 'Θέμα / Λέξη-Κλειδί', 'ai-content-title-assistant' ); ?></label>
                    </th>
                    <td>
                        <input name="aicta_topic" type="text" id="aicta_topic" value="<?php echo esc_attr( $user_topic ); ?>" class="regular-text" required />
                    </td>
                </tr>
            </table>
            
            <?php submit_button( __( 'Generate Titles', 'ai-content-title-assistant' ), 'primary', 'aicta_submit', true ); ?>
        </form>

        <?php if ( ! empty( $generated_titles ) ) : ?>
            <div class="card" style="max-width: 600px; margin-top: 20px; padding: 15px 20px;">
                <h2 style="margin-top: 0;"><?php esc_html_e( 'Προτεινόμενοι SEO Τίτλοι:', 'ai-content-title-assistant' ); ?></h2>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <?php foreach ( $generated_titles as $title ) : ?>
                        <!-- Security: Escaping στο output για προστασία από XSS -->
                        <li style="margin-bottom: 8px; font-size: 14px;"><?php echo esc_html( $title ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
