// For: Smart Phone CF7
// Description: Adds a smart phone input field to Contact Form 7 with international phone number support.




// 1. Register the form-tag generator (admin)
add_action( 'wpcf7_admin_init', function() {
    $tag_generator = WPCF7_TagGenerator::get_instance();
    $tag_generator->add(
        'phone', // Dash style!
        __( 'Smart Phone', 'smart-phone-cf7' ),
        'wpcf7_tag_generator_smart_phone',
        array( 'version' => '2' )
    );
});

function wpcf7_tag_generator_smart_phone( $contact_form, $options ) {
    $field_types = array(
        'phone' => array(
            'display_name' => __( 'Smart Phone Field', 'smart-phone-cf7' ),
            'heading' => __( 'Smart Phone field form-tag generator', 'smart-phone-cf7' ),
            'description' => __( 'Generates a form-tag for a smart phone input field.', 'smart-phone-cf7' ),
            'maybe_purpose' => 'author_tel',
        ),
    );

    $basetype = $options['id'];

    if ( ! in_array( $basetype, array_keys( $field_types ), true ) ) {
        $basetype = 'phone';
    }

    $tgg = new WPCF7_TagGeneratorGenerator( $options['content'] );
?>
<header class="description-box">
    <h3><?php echo esc_html( $field_types[$basetype]['heading'] ); ?></h3>
    <p><?php
        $description = wp_kses(
            $field_types[$basetype]['description'],
            array('a' => array('href' => true),'strong' => array()),
            array('http', 'https')
        );
        echo $description;
    ?></p>
</header>

<div class="control-box">
    <?php
        $tgg->print( 'field_type', array(
            'with_required' => true,
            'select_options' => array(
                $basetype => $field_types[$basetype]['display_name'],
            ),
        ) );
        $tgg->print( 'field_name', array( 'ask_if' => $field_types[$basetype]['maybe_purpose'] ) );
        $tgg->print( 'class_attr' );
        $tgg->print( 'min_max', array(
            'title' => __( 'Length', 'smart-phone-cf7' ),
            'min_option' => 'minlength:',
            'max_option' => 'maxlength:',
        ) );
        $tgg->print( 'default_value', array( 'with_placeholder' => true ) );
    ?>
</div>

<footer class="insert-box">
    <?php
        $tgg->print( 'insert_box_content' );
        $tgg->print( 'mail_tag_tip' );
    ?>
</footer>
<?php
}

// 2. Register the form tag frontend handler
add_action( 'wpcf7_init', function() {
    wpcf7_add_form_tag(
        array( 'phone', 'smart-phone*' ), // Dash style!
        'wpcf7_smart_phone_form_tag_handler',
        array( 'name-attr' => true )
    );
});

// 3. Handler to render the field in the form
function wpcf7_smart_phone_form_tag_handler( $tag ) {
    if ( empty( $tag->name ) ) return '';

    $validation_error = wpcf7_get_validation_error( $tag->name );
    $class = wpcf7_form_controls_class( $tag->type, 'wpcf7-smart-phone' );

    // Support custom classes and ID
    $atts = array();
    $atts['class'] = $tag->get_class_option( $class );
    $atts['id'] = $tag->get_id_option();
    $atts['name'] = $tag->name;
    $atts['type'] = 'tel';

    // Placeholder
    $value = (string) reset( $tag->values );
    if ( $tag->has_option( 'placeholder' ) ) {
        $atts['placeholder'] = $value;
        $value = '';
    }
    $value = $tag->get_default_option( $value );
    $value = wpcf7_get_hangover( $tag->name, $value );
    $atts['value'] = $value;

    // Accessibility
    if ( $tag->is_required() ) $atts['aria-required'] = 'true';
    if ( $validation_error ) {
        $atts['aria-invalid'] = 'true';
        $atts['aria-describedby'] = wpcf7_get_validation_error_reference( $tag->name );
    } else {
        $atts['aria-invalid'] = 'false';
    }

    $input = sprintf(
        '<input %s />',
        wpcf7_format_atts( $atts )
    );

    // Wrapper for custom styling/JS
    $html = sprintf(
        '<span class="wpcf7-form-control-wrap" data-name="%1$s"><span class="smart-phone-wrapper">%2$s</span>%3$s</span>',
        esc_attr( $tag->name ),
        $input,
        $validation_error
    );

    return $html;
}

// 4. Validation (optional, can add your own pattern here)
add_filter( 'wpcf7_validate_smart-phone', 'wpcf7_smart_phone_validate', 10, 2 );
add_filter( 'wpcf7_validate_smart-phone*', 'wpcf7_smart_phone_validate', 10, 2 );
function wpcf7_smart_phone_validate( $result, $tag ) {
    $name = $tag->name;
    $value = isset( $_POST[$name] ) ? trim( $_POST[$name] ) : '';
    // Basic check (you can enhance for strict E.164 or region)
    if ( $tag->is_required() && empty( $value ) ) {
        $result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
    }
    // Optionally validate with regex:
    // elseif ( !preg_match( '/^\+\d{10,15}$/', $value ) ) {
    //     $result->invalidate( $tag, 'Invalid phone format.' );
    // }
    return $result;
}

// 5. Enqueue intl-tel-input JS/CSS (frontend only)
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/css/intlTelInput.min.css' );
    wp_enqueue_script( 'intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/intlTelInput.min.js', [], null, true );
    wp_enqueue_script( 'intl-tel-utils', 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js', ['intl-tel-input'], null, true );
    // Bootstrap Icons (optional, for phone icon)
    wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css' );
    // Custom JS
    wp_add_inline_script( 'intl-tel-utils', wpcf7_smart_phone_custom_js(), 'after' );
    // Custom CSS
    wp_add_inline_style( 'intl-tel-input', wpcf7_smart_phone_custom_css() );
});

// 6. Custom JS to activate intl-tel-input
function wpcf7_smart_phone_custom_js() {
    return <<<JS
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input.wpcf7-smart-phone[type="tel"]').forEach(function(input) {
        if (typeof window.intlTelInput === "function") {
            var iti = window.intlTelInput(input, {
                initialCountry: "auto",
                geoIpLookup: function(callback) {
                    fetch('https://ipapi.co/json')
                        .then(res => res.json())
                        .then(data => callback(data.country_code))
                        .catch(() => callback('us'));
                },
                separateDialCode: false,
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js"
            });

            // Auto-fill dial code if field is empty
            function fillDialCodeIfEmpty() {
                setTimeout(function() {
                    if (input.value.trim() === '') {
                        var countryData = iti.getSelectedCountryData();
                        if (countryData && countryData.dialCode) {
                            input.value = '+' + countryData.dialCode + ' ';
                            input.setSelectionRange(input.value.length, input.value.length);
                        }
                    }
                }, 300);
            }
            input.addEventListener('countrychange', fillDialCodeIfEmpty);
            fillDialCodeIfEmpty();

            // On submit, replace with E.164 format
            var form = input.closest('form.wpcf7-form');
            if (form) {
                form.addEventListener('submit', function() {
                    if (iti && iti.getNumber) {
                        var e164 = iti.getNumber();
                        if (e164) input.value = e164;
                    }
                });
            }
        }
    });
});
JS;
}

// 7. Custom CSS for better layout and flag popup
function wpcf7_smart_phone_custom_css() {
    return <<<CSS
.smart-phone-wrapper { position: relative; display: block; }
input.wpcf7-smart-phone[type="tel"] { padding-left: 62px !important; width:100%; }
.smart-phone-wrapper .iti { width:100%!important; }
.smart-phone-wrapper .iti__flag-container { left: 0 !important; }
.iti__country-list {
    width: 340px !important; min-width: 220px !important; max-width: 400px !important;
    max-height: 270px !important; overflow-y: auto !important;
    border-radius: 0.5rem !important; box-shadow: 0 8px 24px rgba(0,0,0,0.16) !important;
    font-size: 1rem !important; left: 0 !important; top: 100% !important;
    position: absolute !important; background: #fff !important; z-index: 9999 !important; padding: 0 !important;
}
.iti--container { z-index: 9999 !important; }
.iti__country-list .iti__country { padding: 0.375rem 1rem !important; border-bottom: 1px solid #f5f5f5 !important;}
.iti__country-list .iti__country:last-child { border-bottom: none !important;}
.iti__country-list.hide { display: none !important; }
@media (max-width: 480px) {
    .iti__country-list { width: 100vw !important; left: 0 !important; right: 0 !important; min-width: 0 !important; max-width: 100vw !important;}
}
CSS;
}
