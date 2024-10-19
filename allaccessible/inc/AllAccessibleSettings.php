<?php
$license = "Free for WordPress";
$premium = false;
if(get_option('aacb_accountID')) {
    $license = "Premium";
    $premium = true;
}?>

<div class="aacb-main">
    <div class="aacb-nav-bar">
        <div class="aacb-header">
            <div class="aacb-row aacb-align-center">
                <div class="aacb-col-4"><a href="https://www.allaccessible.org/" target="_blank"><img src="<?php echo AACB_IMG;?>logo.svg" height="41px" alt="AllAccessible Logo"></a></div>
                <div class="aacb-col-8">
                    <div class="aacb-card-header-actions">
                        <div class="aacb-card-row-actions"><a class="aacb-button aacb-button-outline aacb-button-medium" href="<?php get_bloginfo('url'); ?>/?aacb_preview=true" target="_blank"><?php _e( 'Preview Widget', 'allaccessible' ); ?></a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="aacb-app-body">
        <div class="aacb-section aacb-section-dashboard aacb-zero--padding aacb-zero--margin">
            <div class="aacb-row">
                <div class="aacb-col-12">
                </div>
            </div>
            <div class="aacb-section-content">
                <div class="aacb-row">
                    <div class="aacb-col-12">
                        <div class="aacb-dashboard-overview">
                            <div class="aacb-row">
                                <div class="aacb-col-4">
                                    <div class="aacb-card">
                                        <div class="aacb-card-body">
                                            <div class="aacb-card-row">
                                                <div class="aacb-info-widget-container">
                                                    <div class="aacb-info-widget">
                                                        <div class="aacb-info-widget-icon"><img src="<?php echo AACB_IMG;?>bug.svg" height="41px" width="41px" alt="layout"></div>
                                                        <div class="aacb-info-widget-content"><span class="aacb-info-widget-title"><?php _e( 'Widget Status', 'allaccessible' ); ?></span><span class="aacb-info-widget-text" style="color: rgb(0, 170, 98);"> <?php _e( 'Active', 'allaccessible' ); ?> </span></div>
                                                        <p class="aacb-info-widget-description"><?php printf( __( 'AllAccessible\'s %s widget is visible on the front end of your site.', 'allaccessible' ), $license ); ?></p>
                                                        <div class="aacb-card-row-actions"><a class="aacb-button aacb-button-outline aacb-button-medium" href="<?php get_bloginfo('url'); ?>/?aacb_preview=true" target="_blank"><?php _e( 'Preview Widget', 'allaccessible' ); ?></a></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="aacb-col-4">
                                    <div class="aacb-card">
                                        <div class="aacb-card-body">
                                            <div class="aacb-card-row">
                                                <div class="aacb-info-widget-container">
                                                    <div class="aacb-info-widget">
                                                        <div class="aacb-info-widget-icon">
                                                            <!--https://www.svgrepo.com/collection/carbon-design-line-icons/-->
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="#000000" width="41px" height="41px" viewBox="0 0 32 32" id="icon"><defs><style>.cls-1{fill:none;}</style></defs><title>dashboard</title><rect x="24" y="21" width="2" height="5"/><rect x="20" y="16" width="2" height="10"/><path d="M11,26a5.0059,5.0059,0,0,1-5-5H8a3,3,0,1,0,3-3V16a5,5,0,0,1,0,10Z"/><path d="M28,2H4A2.002,2.002,0,0,0,2,4V28a2.0023,2.0023,0,0,0,2,2H28a2.0027,2.0027,0,0,0,2-2V4A2.0023,2.0023,0,0,0,28,2Zm0,9H14V4H28ZM12,4v7H4V4ZM4,28V13H28.0007l.0013,15Z"/><rect id="_Transparent_Rectangle_" data-name="&lt;Transparent Rectangle&gt;" class="cls-1" width="32" height="32"/></svg>
                                                        </div>
                                                        <div class="aacb-info-widget-content"><span class="aacb-info-widget-title"><?php _e( 'Website Accessibility', 'allaccessible' ); ?></span><span class="aacb-info-widget-text"> <?php _e( 'Audits & Remediation', 'allaccessible' ); ?></span></div>
                                                        <?php if(!$premium) { ?>
                                                            <p class="aacb-info-widget-description"><?php _e( 'View the results of your website scans, accessibility Remediation and more with AllAccessibile Premium.', 'allaccessible' ); ?></p>
                                                            <div class="aacb-card-row-actions"><a class="aacb-button aacb-button-outline aacb-button-medium" href="#pluginSettings" disabled><?php _e( 'Upgrade Now', 'allaccessible' ); ?></a></div>
                                                        <?php } else { ?>
                                                            <p class="aacb-info-widget-description"><?php _e( 'View the results of your website scans in your AllAccessibile Account Dashboard.', 'allaccessible' ); ?></p>
                                                            <div class="aacb-card-row-actions"><a class="aacb-button aacb-button-outline aacb-button-medium" href="https://app.allaccessible.org" target="_blank"><?php _e( 'Accessibility Dashboard', 'allaccessible' ); ?></a></div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="aacb-col-4">
                                    <div class="aacb-card">
                                        <div class="aacb-card-body">
                                            <div class="aacb-card-row">
                                                <div class="aacb-info-widget-container">
                                                    <div class="aacb-info-widget">
                                                        <div class="aacb-info-widget-icon">
                                                            <!--https://www.svgrepo.com/svg/503998/question-answer-help -->
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="41px" height="41px" viewBox="0 0 24 24" fill="none"><path d="M4 21V19.5C4 16.4624 6.46243 14 9.5 14H12.5C15.5376 14 18 16.4624 18 19.5V21M7 21V18M15 21V18M16.5 6L16.8367 5.49493C17.1969 4.95461 17.9371 4.82782 18.4566 5.21745C19.0073 5.63047 19.0645 6.43549 18.5778 6.92224L17.8536 7.64645C17.6272 7.87282 17.5 8.17986 17.5 8.5M17.5 10V10.2M13.8281 4.89801C14.6435 3.74945 15.9842 3 17.5 3C19.9853 3 22 5.01472 22 7.5C22 9.98528 19.9853 12 17.5 12C16.2549 12 15.1279 11.4943 14.3131 10.6771M15 8.00001C15 5.79086 13.2091 4 11 4C8.79086 4 7 5.79086 7 8.00001C7 10.2092 8.79086 12 11 12C11.8312 12 12.6032 11.7465 13.2429 11.3125C14.3033 10.5931 15 9.37794 15 8.00001Z" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"/></svg>
                                                        </div>
                                                        <div class="aacb-info-widget-content"><span class="aacb-info-widget-title"><?php _e( 'Need', 'allaccessible' ); ?></span><span class="aacb-info-widget-text" > <?php _e( 'Help & Support?', 'allaccessible' ); ?> </span></div>
                                                        <p class="aacb-info-widget-description"><?php _e( 'Check our knowledge base for assistance with installation, customization, and more.', 'allaccessible' ); ?></p>
                                                        <div class="aacb-card-row-actions"><a class="aacb-button aacb-button-outline aacb-button-medium" href="https://support.allaccessible.org" target="_blank"><?php _e( 'Get Support', 'allaccessible' ); ?></a></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!---->
                <?php
                $user_id = get_current_user_id();
                $user_info = get_userdata($user_id);
                if (!empty($message)) : ?>
                    <div class="message notice <?php echo esc_attr($class); ?>">
                        <p><?php echo esc_attr($message); ?></p>
                    </div>
                <?php endif; ?>
                <div class="aacb-card aacb-settings" style="width:100%;" id="siteDetails" name="pluginSettings" data-siteid="<?php echo esc_attr(get_option('aacb_siteID')); ?>">
                    <div class="aacb-row">
                        <div class="aacb-card-body aacb-col-12">
                        <?php if(!get_option('aacb_accountID')){ ?>
                            <h3><?php _e( 'Unlock the Power of AllAccessible Premium', 'allaccessible' ); ?></h3>
                            <p class="lead"><?php printf( __( 'Unlock all the benefits of our Ai accessibility automation with a free trial by connecting your AllAccessible account to your email below. Already using the free version? No problem! We\'ll set you up with a free 7-day trial of our premium version. After the trial period, upgrade to the premium version for just $10 a month and enjoy a more accessible digital experience.', 'allaccessible' ), get_bloginfo( 'name' ) ); ?>.</p>
                            <div class="aacb-row" style="min-height:250px;">
                                <div class="aacb-col-6">
                                    <p><?php _e( 'Below are some additional features unlocked with AllAccessible Premium. All for Only $10/mo.', 'allaccessible' ); ?><br />
                                        ✅ <?php _e( 'User Control Panel', 'allaccessible' ); ?><br />
                                        ✅ <?php _e( 'Accessibility Statement', 'allaccessible' ); ?><br />
                                        ✅ <?php _e( 'Widget Customization (Color, Shape, Placement, etc.)', 'allaccessible' ); ?><br />
                                        ✅ <?php _e( 'Ai Accessibility Fixes', 'allaccessible' ); ?><br />
                                        ✅ <?php _e( 'Image Alt Text Generation', 'allaccessible' ); ?><br />
                                        ✅ <?php _e( 'Monthly Accessibility Audits', 'allaccessible' ); ?><br />
                                        ✅ <?php _e( 'Accessibility Accommodation Requests', 'allaccessible' ); ?><br />
                                    </p>
                                </div>
                                <div class="aacb-col-6">
                                    <iframe src="https://www.youtube.com/embed/Y7RxL_iM1Tc?rel=0" allowfullscreen="allowfullscreen" sandbox="allow-scripts allow-same-origin allow-popups allow-presentation" class="youtube-player" style="width: 100%; height: 100%;"></iframe>
                                </div>
                            </div>
                            <p><?php _e( 'To learn more, visit: https://www.allaccessible.org', 'allaccessible' ); ?></p>
                            <form class="aacb-form" id="AACB_trialForm">
                                <!--                                <p>Create a free 7 day trial for --><?php //echo get_bloginfo( 'name' ); ?><!--. All you need to do is verify your email below and hit submit. If you don't have an account, we'll automatically create one for you and email you a confirmation. If you already have an account, we'll add this site to your dashboard. We'll create a 7-day trial for your site.</p>-->
                                <div class="form-group">
                                    <label for="aacb_email"><?php esc_attr_e( 'Your Email', 'allaccessible' ); ?> <?php printf( __( '(Used to manage your Account on %s)', 'allaccessible' ), AACB_NAME ); ?></label>
                                    <input type="text" class="form-control" id="aacb_email" name="aacb_email" value="<?php echo esc_attr($user_info->user_email); ?>">
                                    <input type="hidden" name="aacb_url" id="aacb_url" value="<?php echo get_bloginfo('wpurl'); ?>">
                                </div>
                                <input class="btn btn-primary" type="submit" id="aacb-trial-btn" value="<?php esc_attr_e( __( 'Upgrade to Premium', 'allaccessible' ) ); ?>">
                            </form>
                        <?php } else { ?>
                            <h3><?php _e( 'Plugin Configuration', 'allaccessible' ); ?></h3>
                            <p><?php printf( __( '%1$s is connected to %2$s with account ID %3$s', 'allaccessible' ), get_bloginfo('wpurl'), AACB_NAME, esc_attr(get_option('aacb_accountID')) ); ?></p>
                            <?php if($GLOBALS['aacb_siteOptions']){ ?>
                            <div class="aacb-row" style="min-height:250px;">
                                <div class="aacb-col-6">
                                    <div class="">
                                        <div class="aacb_preview-window">
                                            <div class="aacb_preview-window__button"></div>
                                            <div class="aacb_preview-window__toolbar"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="aacb-col-6">
                                    <form class="aacb-form" id="AACB_optionsForm">
                                        <div class="aacb-row form-group">
                                            <div class="aacb-col-6">
                                                <label for="colorPicker" class="aacb_form-label">
                                                    <?php
                                                    $widgetColor = "#2b446d";
                                                    if(isset($GLOBALS['aacb_siteOptions']->triggerBtnBg)) {
                                                        $widgetColor = $GLOBALS['aacb_siteOptions']->triggerBtnBg;
                                                    }
                                                    ?>
                                                    <p><?php _e( 'Choose Widget Color', 'allaccessible' ); ?> <?php echo $widgetColor; ?></p>
                                                </label>
                                                <input type="text" id="colorPicker" class="aacb_form-control aacb_h-60px" name="triggerBtnBg" value="<?php if(isset($GLOBALS['aacb_siteOptions']->triggerBtnBg)){ echo $GLOBALS['aacb_siteOptions']->triggerBtnBg; } else { echo "#2b446d"; } ?>" placeholder=""/>
                                            </div>

                                            <div class="aacb-col-6">
                                                <label for="kt_docs_select2_icon" class="mb-2"><?php _e( 'Choose Icon', 'allaccessible' ); ?></label>
                                                <select class="form-select form-select-transparent aacb_h-60px" name="triggerSVG" id="kt_docs_select2_icon" >
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Default'){?>selected<?php } ?> value="Default" data-kt-select2-icon="Default"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Alt'){?>selected<?php } ?> value="Alt" data-kt-select2-icon="Alt"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Adjust'){?>selected<?php } ?> value="Adjust" data-kt-select2-icon="Adjust"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Chair2'){?>selected<?php } ?> value="Chair2" data-kt-select2-icon="Chair2"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Chair3'){?>selected<?php } ?> value="Chair3" data-kt-select2-icon="Chair3"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Heart'){?>selected<?php } ?> value="Heart" data-kt-select2-icon="Heart"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Braille'){?>selected<?php } ?> value="Braille" data-kt-select2-icon="Braille"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Blind'){?>selected<?php } ?> value="Blind" data-kt-select2-icon="Blind"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Eye'){?>selected<?php } ?> value="Eye" data-kt-select2-icon="Eye"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Globe'){?>selected<?php } ?> value="Globe" data-kt-select2-icon="Globe"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Access'){?>selected<?php } ?> value="Access" data-kt-select2-icon="Access"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Alt2'){?>selected<?php } ?> value="Alt2" data-kt-select2-icon="Alt2"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Cogs'){?>selected<?php } ?> value="Cogs" data-kt-select2-icon="Cogs"></option>
                                                    <option <?php if($GLOBALS['aacb_siteOptions']->triggerSVG == 'Cane'){?>selected<?php } ?> value="Cane" data-kt-select2-icon="Cane"></option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="aacb-row form-group">
                                            <div class="aacb-col-6">
                                                <label class="mb-2" ><?php _e( 'Choose Widget Size', 'allaccessible' ); ?></label>
                                                <select class="form-select" id="sizeSelect" data-control="select2" name="triggerBtnSize" data-hide-search="true" data-placeholder="<?php _e( 'Choose Widget Size', 'allaccessible' ); ?>">
                                                    <?php $sizes = array(
                                                        '40' => __( 'Small', 'allaccessible' ),
                                                        '55' => __( 'Medium (Default)', 'allaccessible' ),
                                                        '80' => __( 'Large', 'allaccessible' ),
                                                    ); ?>
                                                    <?php foreach ($sizes as $value => $label) : ?>
                                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($GLOBALS['aacb_siteOptions']->triggerBtnSize, $value); ?>><?php echo esc_html($label); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="aacb-col-6">
                                                <label for="buttonPosition"><?php esc_html_e( 'Choose Widget Position', 'allaccessible' ); ?></label>
                                                <select id="buttonPosition" name="buttonPosition">
                                                    <option value="bottom-right" <?php echo ($GLOBALS['aacb_siteOptions']->buttonPosition ?? 'bottom-right') === 'bottom-right' ? 'selected' : ''; ?>><?php esc_html_e( 'Bottom Right', 'allaccessible' ); ?></option>
                                                    <option value="right-center" <?php echo ($GLOBALS['aacb_siteOptions']->buttonPosition ?? 'bottom-right') === 'right-center' ? 'selected' : ''; ?>><?php esc_html_e( 'Middle Right', 'allaccessible' ); ?></option>
                                                    <option value="top-right" <?php echo ($GLOBALS['aacb_siteOptions']->buttonPosition ?? 'bottom-right') === 'top-right' ? 'selected' : ''; ?>><?php esc_html_e( 'Middle Right', 'allaccessible' ); ?></option>
                                                    <option value="bottom-center" <?php echo ($GLOBALS['aacb_siteOptions']->buttonPosition ?? 'bottom-right') === 'bottom-center' ? 'selected' : ''; ?>><?php esc_html_e( 'Bottom Center', 'allaccessible' ); ?></option>
                                                    <option value="bottom-left" <?php echo ($GLOBALS['aacb_siteOptions']->buttonPosition ?? 'bottom-right') === 'bottom-left' ? 'selected' : ''; ?>><?php esc_html_e( 'Bottom Left', 'allaccessible' ); ?></option>
                                                    <option value="left-center" <?php echo ($GLOBALS['aacb_siteOptions']->buttonPosition ?? 'bottom-right') === 'left-center' ? 'selected' : ''; ?>><?php esc_html_e( 'Middle Left', 'allaccessible' ); ?></option>
                                                    <option value="top-left" <?php echo ($GLOBALS['aacb_siteOptions']->buttonPosition ?? 'bottom-right') === 'top-left' ? 'selected' : ''; ?>><?php esc_html_e( 'Top Left', 'allaccessible' ); ?></option>
                                                </select><br />
                                            </div>
                                        </div>
                                        <div class="aacb-row form-group">
                                            <div class="aacb-col-6">
                                                <label class="mb-2"><?php _e( 'Choose Widget Shape', 'allaccessible' ); ?></label>
                                                <select class="form-select" id="shapeSelect" data-control="select2" name="triggerBtnRadius" data-hide-search="true" data-placeholder="<?php _e( 'Choose Widget Shape', 'allaccessible' ); ?>">
                                                    <?php
                                                    $options = array(
                                                        array('label' => __('Circle', 'allaccessible'), 'value' => '50'),
                                                        array('label' => __('Square', 'allaccessible'), 'value' => '0'),
                                                    );
                                                    foreach ($options as $option) {
                                                        $selected = '';
                                                        if (isset($GLOBALS['aacb_siteOptions']->triggerBtnRadius) && $GLOBALS['aacb_siteOptions']->triggerBtnRadius == $option['value']) {
                                                            $selected = 'selected';
                                                        }
                                                        echo '<option value="' . $option['value'] . '" ' . $selected . '>' . $option['label'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="aacb-col-6">
                                                <label for="isWhitelabel"><?php _e( 'Hide AllAccessible Branding', 'allaccessible' ); ?></label>
                                                <select class="form-select" data-control="select2" name="isWhitelabel" id="isWhitelabel" data-hide-search="true" data-placeholder="<?php _e( 'Remove Branding', 'allaccessible' ); ?>" >
                                                    <option value=""></option>
                                                    <option value="false" <?php selected( !$GLOBALS['aacb_siteOptions']->isWhitelabel && isset($GLOBALS['aacb_siteOptions']->isWhitelabel) ); ?>><?php _e( 'Branding Visible', 'allaccessible' ); ?></option>
                                                    <option value="true" <?php selected( $GLOBALS['aacb_siteOptions']->isWhitelabel ); ?>><?php _e( 'Branding Removed', 'allaccessible' ); ?></option>
                                                </select>
                                                <?php if ( $GLOBALS['aacb_siteOptions']->isWhitelabel ) : ?>
                                                    <p class="aacb-note"><?php _e( 'Note: Hiding the branding will remove the "Powered by AllAccessible" text and logo from the widget.', 'allaccessible' ); ?></p>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                        <input class="btn btn-primary" id="aacb-save-opt-btn" type="submit" value="<?php _e( 'Save Settings', 'allaccessible' ); ?>">
                                    </form>
                                    <div class="aacb_message" id="aacb_message"></div>
                                </div>
                            </div>
                            <?php } else { ?>
                                <p><?php _e( 'You may need to reload this page if your widget options are not visible', 'allaccessible' ); ?></p>
                            <?php } ?>

                        <?php } ?>
                        </div>
                    </div>
                    <div class="aacb-row">
                        <p style="padding-top:40px;"><strong><?php echo AACB_NAME; ?></strong> <i><?php _e( 'Version', 'allaccessible' ); ?> <?php echo AACB_VERSION; ?></i><br />
                            <?php _e( 'Need Help', 'allaccessible' ); ?>? <a href="<?php echo  esc_url(AACB_SUPPORT); ?>" target="_blank"><?php _e( 'Contact Us', 'allaccessible' ); ?></a>
                        </p>
                    </div>
                    <script>
                        jQuery(document).ready(function ($) {

                            //scroll to
                            $('a[href^="#"]').click(function() {
                                var target = $(this.hash);
                                if (target.length == 0) target = $('a[name="' + this.hash.substr(1) + '"]');
                                if (target.length == 0) target = $('html');
                                $('html, body').animate({ scrollTop: target.offset().top }, 700);
                                return false;
                            });

                            const svgIcons = {
                                Default: '<svg version="1.1" x="0px" y="0px" viewBox="0 0 79.583 79.638" style="padding:10px;" xmlns="http://www.w3.org/2000/svg"> <title>Open Accessibility Panel</title> <g transform="matrix(1, 0, 0, 1, -10.250157, -10.143444)"> <path d="M55.6,73.2c0.5,1.1,1.5,1.7,2.6,1.7c0.4,0,0.8-0.1,1.2-0.3c1.5-0.7,2.1-2.4,1.4-3.9c0,0-5.5-12.6-6.5-17.2   c-0.4-1.6-0.6-5.9-0.7-7.9c0-0.7,0.4-1.3,1-1.5l12.3-3.7c1.5-0.4,2.4-2.1,2-3.6c-0.4-1.5-2.1-2.4-3.6-2c0,0-11.4,3.7-15.5,3.7   c-4,0-15.3-3.6-15.3-3.6c-1.5-0.4-3.2,0.3-3.7,1.8c-0.5,1.6,0.4,3.3,2,3.7l12.3,3.7c0.6,0.2,1.1,0.8,1,1.5c-0.1,2-0.3,6.3-0.7,7.9   c-1,4.6-6.5,17.2-6.5,17.2c-0.7,1.5,0,3.2,1.4,3.9c0.4,0.2,0.8,0.3,1.2,0.3c1.1,0,2.2-0.6,2.6-1.7L50,61.2L55.6,73.2z"/> <circle cx="50" cy="30" r="5.6"/> <path d="M89.5,50c0-21.8-17.7-39.5-39.5-39.5c-21.8,0-39.5,17.7-39.5,39.5S28.2,89.5,50,89.5C71.8,89.5,89.5,71.8,89.5,50z    M17.1,50c0-18.2,14.8-32.9,32.9-32.9S82.9,31.8,82.9,50S68.2,82.9,50,82.9S17.1,68.2,17.1,50z"/> </g> </svg>',
                                Alt: '<svg version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 182.608 214.637" style="padding:10px;" xmlns="http://www.w3.org/2000/svg"> <g transform="matrix(1, 0, 0, 1, -15.892562, 0.061192)"> <path d="M127.75,183.153c-10.629,10.63-24.76,16.483-39.793,16.483c-31.024,0-56.265-25.241-56.265-56.268 c0-15.034,5.852-29.168,16.479-39.796c2.929-2.929,2.928-7.678-0.001-10.607c-2.929-2.93-7.678-2.929-10.607,0.001 c-13.459,13.461-20.871,31.36-20.871,50.401c0,39.297,31.969,71.268,71.265,71.268c19.039,0,36.938-7.414,50.4-20.878 c2.929-2.929,2.928-7.678-0.001-10.606C135.427,180.225,130.678,180.224,127.75,183.153z"/> <path d="M190.444,166.706h-21.335L155.9,130.057c-1.072-2.975-3.894-4.957-7.056-4.957H93.232V91.424h51.648 c4.142,0,7.5-3.357,7.5-7.5c0-4.143-3.358-7.5-7.5-7.5H93.232V53.279c0-4.143-3.358-7.5-7.5-7.5c-4.142,0-7.5,3.357-7.5,7.5v79.32 c0,4.143,3.358,7.5,7.5,7.5h57.842l13.209,36.649c1.072,2.975,3.894,4.957,7.056,4.957h26.604c4.142,0,7.5-3.357,7.5-7.5 S194.587,166.706,190.444,166.706z"/> <path d="M86.015,36.438c10.063,0,18.221-8.154,18.221-18.224C104.235,8.161,96.078,0,86.015,0C75.952,0,67.796,8.161,67.796,18.215 C67.796,28.284,75.952,36.438,86.015,36.438z"/> </g> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> <g/> </svg>',
                                Alt2: '<svg x="0px" y="0px" viewBox="0.634 0.344 56.406 60.613" enable-background="new 0 0 100 100" style="padding:10px;" xmlns="http://www.w3.org/2000/svg"> <g transform="matrix(1, 0, 0, 1, -21.093462, -19.272371)"> <path d="M50.1,41.3c-4.9,0-8.8,3.9-8.8,8.8s3.9,8.8,8.8,8.8s8.8-3.9,8.8-8.8S55,41.3,50.1,41.3z M76.4,58l-4.6-3.9 c0.3-1.4,0.4-2.9,0.4-4.3c0-1.4-0.1-2.9-0.4-4.3l4.6-3.9c1.5-1.3,2-3.5,1-5.3l-2-3.5c-0.8-1.3-2.1-2-3.6-2c-0.5,0-1,0.1-1.4,0.3 l-5.8,2.1c-2.3-2-4.8-3.4-7.4-4.3l-1-5.9c-0.4-2-2.1-3.1-4.1-3.1h-4c-2,0-3.8,1.1-4.1,3.1l-1,5.8c-2.8,0.9-5.3,2.4-7.5,4.3L29.6,31 c-0.5-0.1-0.9-0.3-1.4-0.3c-1.5,0-2.9,0.8-3.6,2l-2,3.5c-1,1.8-0.6,4,1,5.3l4.6,3.9c-0.3,1.4-0.4,2.9-0.4,4.3 c0,1.5,0.1,2.9,0.4,4.3l-4.6,3.9c-1.5,1.3-2,3.5-1,5.3l2,3.5c0.8,1.3,2.1,2,3.6,2c0.5,0,1-0.1,1.4-0.3l5.8-2.1 c2.3,2,4.8,3.4,7.4,4.3l1,6c0.4,2,2,3.4,4.1,3.4h4c2,0,3.8-1.5,4.1-3.5l1-6c2.9-1,5.5-2.5,7.8-4.6l5.4,2.1c0.5,0.1,1,0.3,1.5,0.3 c1.5,0,2.9-0.8,3.6-2l1.9-3.3C78.4,61.5,77.9,59.3,76.4,58z M50.1,63.9c-7.7,0-13.8-6.2-13.8-13.8s6.2-13.8,13.8-13.8 s13.8,6.2,13.8,13.8S57.7,63.9,50.1,63.9z"/> </g> </svg>',
                                Adjust: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="padding:10px;"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path fill="#ffffff" d="M8 256c0 136.966 111.033 248 248 248s248-111.034 248-248S392.966 8 256 8 8 119.033 8 256zm248 184V72c101.705 0 184 82.311 184 184 0 101.705-82.311 184-184 184z"></path></svg>',
                                Braille: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" style="padding:10px;"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path fill="#ffffff" d="M128 256c0 35.346-28.654 64-64 64S0 291.346 0 256s28.654-64 64-64 64 28.654 64 64zM64 384c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-352C28.654 32 0 60.654 0 96s28.654 64 64 64 64-28.654 64-64-28.654-64-64-64zm160 192c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0 160c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-352c-35.346 0-64 28.654-64 64s28.654 64 64 64 64-28.654 64-64-28.654-64-64-64zm224 192c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0 160c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-352c-35.346 0-64 28.654-64 64s28.654 64 64 64 64-28.654 64-64-28.654-64-64-64zm160 192c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0 160c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-320c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32z"></path></svg>',
                                Blind: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" enable-background="new 0 0 100 100" style="padding:10px;"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path fill="#ffffff" d="M380.15 510.837a8 8 0 0 1-10.989-2.687l-125.33-206.427a31.923 31.923 0 0 0 12.958-9.485l126.048 207.608a8 8 0 0 1-2.687 10.991zM142.803 314.338l-32.54 89.485 36.12 88.285c6.693 16.36 25.377 24.192 41.733 17.501 16.357-6.692 24.193-25.376 17.501-41.734l-62.814-153.537zM96 88c24.301 0 44-19.699 44-44S120.301 0 96 0 52 19.699 52 44s19.699 44 44 44zm154.837 169.128l-120-152c-4.733-5.995-11.75-9.108-18.837-9.112V96H80v.026c-7.146.003-14.217 3.161-18.944 9.24L0 183.766v95.694c0 13.455 11.011 24.791 24.464 24.536C37.505 303.748 48 293.1 48 280v-79.766l16-20.571v140.698L9.927 469.055c-6.04 16.609 2.528 34.969 19.138 41.009 16.602 6.039 34.968-2.524 41.009-19.138L136 309.638V202.441l-31.406-39.816a4 4 0 1 1 6.269-4.971l102.3 129.217c9.145 11.584 24.368 11.339 33.708 3.965 10.41-8.216 12.159-23.334 3.966-33.708z"></path></svg>',
                                Eye: '<svg style="padding:10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c5.2-11.8 8-24.8 8-38.5c0-53-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zm223.1 298L83.1 161.5c-11 14.4-20.5 28.7-28.4 42.2l339 265.7c18.7-5.5 36.2-13 52.6-21.8zM34.5 268.3c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c3.1 0 6.1-.1 9.2-.2L33.1 247.8c-1.8 6.8-1.3 14 1.4 20.5z"/></svg>',
                                Cogs: '<svg style="padding:10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M224 256c-70.7 0-128-57.3-128-128S153.3 0 224 0s128 57.3 128 128s-57.3 128-128 128zm-45.7 48h91.4c11.8 0 23.4 1.2 34.5 3.3c-2.1 18.5 7.4 35.6 21.8 44.8c-16.6 10.6-26.7 31.6-20 53.3c4 12.9 9.4 25.5 16.4 37.6s15.2 23.1 24.4 33c15.7 16.9 39.6 18.4 57.2 8.7v.9c0 9.2 2.7 18.5 7.9 26.3H29.7C13.3 512 0 498.7 0 482.3C0 383.8 79.8 304 178.3 304zM436 218.2c0-7 4.5-13.3 11.3-14.8c10.5-2.4 21.5-3.7 32.7-3.7s22.2 1.3 32.7 3.7c6.8 1.5 11.3 7.8 11.3 14.8v30.6c7.9 3.4 15.4 7.7 22.3 12.8l24.9-14.3c6.1-3.5 13.7-2.7 18.5 2.4c7.6 8.1 14.3 17.2 20.1 27.2s10.3 20.4 13.5 31c2.1 6.7-1.1 13.7-7.2 17.2l-25 14.4c.4 4 .7 8.1 .7 12.3s-.2 8.2-.7 12.3l25 14.4c6.1 3.5 9.2 10.5 7.2 17.2c-3.3 10.6-7.8 21-13.5 31s-12.5 19.1-20.1 27.2c-4.8 5.1-12.5 5.9-18.5 2.4l-24.9-14.3c-6.9 5.1-14.3 9.4-22.3 12.8l0 30.6c0 7-4.5 13.3-11.3 14.8c-10.5 2.4-21.5 3.7-32.7 3.7s-22.2-1.3-32.7-3.7c-6.8-1.5-11.3-7.8-11.3-14.8V454.8c-8-3.4-15.6-7.7-22.5-12.9l-24.7 14.3c-6.1 3.5-13.7 2.7-18.5-2.4c-7.6-8.1-14.3-17.2-20.1-27.2s-10.3-20.4-13.5-31c-2.1-6.7 1.1-13.7 7.2-17.2l24.8-14.3c-.4-4.1-.7-8.2-.7-12.4s.2-8.3 .7-12.4L343.8 325c-6.1-3.5-9.2-10.5-7.2-17.2c3.3-10.6 7.7-21 13.5-31s12.5-19.1 20.1-27.2c4.8-5.1 12.4-5.9 18.5-2.4l24.8 14.3c6.9-5.1 14.5-9.4 22.5-12.9V218.2zm92.1 133.5c0-26.5-21.5-48-48.1-48s-48.1 21.5-48.1 48s21.5 48 48.1 48s48.1-21.5 48.1-48z"/></svg>',
                                Globe: '<svg style="padding:10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M352 256c0 22.2-1.2 43.6-3.3 64H163.3c-2.2-20.4-3.3-41.8-3.3-64s1.2-43.6 3.3-64H348.7c2.2 20.4 3.3 41.8 3.3 64zm28.8-64H503.9c5.3 20.5 8.1 41.9 8.1 64s-2.8 43.5-8.1 64H380.8c2.1-20.6 3.2-42 3.2-64s-1.1-43.4-3.2-64zm112.6-32H376.7c-10-63.9-29.8-117.4-55.3-151.6c78.3 20.7 142 77.5 171.9 151.6zm-149.1 0H167.7c6.1-36.4 15.5-68.6 27-94.7c10.5-23.6 22.2-40.7 33.5-51.5C239.4 3.2 248.7 0 256 0s16.6 3.2 27.8 13.8c11.3 10.8 23 27.9 33.5 51.5c11.6 26 21 58.2 27 94.7zm-209 0H18.6C48.6 85.9 112.2 29.1 190.6 8.4C165.1 42.6 145.3 96.1 135.3 160zM8.1 192H131.2c-2.1 20.6-3.2 42-3.2 64s1.1 43.4 3.2 64H8.1C2.8 299.5 0 278.1 0 256s2.8-43.5 8.1-64zM194.7 446.6c-11.6-26-20.9-58.2-27-94.6H344.3c-6.1 36.4-15.5 68.6-27 94.6c-10.5 23.6-22.2 40.7-33.5 51.5C272.6 508.8 263.3 512 256 512s-16.6-3.2-27.8-13.8c-11.3-10.8-23-27.9-33.5-51.5zM135.3 352c10 63.9 29.8 117.4 55.3 151.6C112.2 482.9 48.6 426.1 18.6 352H135.3zm358.1 0c-30 74.1-93.6 130.9-171.9 151.6c25.5-34.2 45.2-87.7 55.3-151.6H493.4z"/></svg>',
                                Heart: '<svg x="0px" y="0px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="padding:10px;"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M244 84L255.1 96L267.1 84.02C300.6 51.37 347 36.51 392.6 44.1C461.5 55.58 512 115.2 512 185.1V190.9C512 232.4 494.8 272.1 464.4 300.4L283.7 469.1C276.2 476.1 266.3 480 256 480C245.7 480 235.8 476.1 228.3 469.1L47.59 300.4C17.23 272.1 0 232.4 0 190.9V185.1C0 115.2 50.52 55.58 119.4 44.1C164.1 36.51 211.4 51.37 244 84C243.1 84 244 84.01 244 84L244 84zM255.1 163.9L210.1 117.1C188.4 96.28 157.6 86.4 127.3 91.44C81.55 99.07 48 138.7 48 185.1V190.9C48 219.1 59.71 246.1 80.34 265.3L256 429.3L431.7 265.3C452.3 246.1 464 219.1 464 190.9V185.1C464 138.7 430.4 99.07 384.7 91.44C354.4 86.4 323.6 96.28 301.9 117.1L255.1 163.9z"/></svg></xml>',
                                Chair2:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="padding:10px;"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M416 48c0 26.5-21.5 48-48 48s-48-21.5-48-48s21.5-48 48-48s48 21.5 48 48zM204.5 121.3c-5.4-2.5-11.7-1.9-16.4 1.7l-40.9 30.7c-14.1 10.6-34.2 7.7-44.8-6.4s-7.7-34.2 6.4-44.8l40.9-30.7c23.7-17.8 55.3-21 82.1-8.4l90.4 42.5c29.1 13.7 36.8 51.6 15.2 75.5L299.1 224h97.4c30.3 0 53 27.7 47.1 57.4L415.4 422.3c-3.5 17.3-20.3 28.6-37.7 25.1s-28.6-20.3-25.1-37.7L377 288H306.7c8.6 19.6 13.3 41.2 13.3 64c0 88.4-71.6 160-160 160S0 440.4 0 352s71.6-160 160-160c11.1 0 22 1.1 32.4 3.3l54.2-54.2-42.1-19.8zM160 448c53 0 96-43 96-96s-43-96-96-96s-96 43-96 96s43 96 96 96z"/></svg>',
                                Chair3: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" style="padding:10px;"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M224 96c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48zM152.5 247.2c12.4-4.7 18.7-18.5 14-30.9s-18.5-18.7-30.9-14C75.1 225.1 32 283.5 32 352c0 88.4 71.6 160 160 160c61.2 0 114.3-34.3 141.2-84.7c6.2-11.7 1.8-26.2-9.9-32.5s-26.2-1.8-32.5 9.9C272 440 234.8 464 192 464c-61.9 0-112-50.1-112-112c0-47.9 30.1-88.8 72.5-104.8zM291.8 176l-1.9-9.7c-4.5-22.3-24-38.3-46.8-38.3c-30.1 0-52.7 27.5-46.8 57l23.1 115.5c6 29.9 32.2 51.4 62.8 51.4h5.1c.4 0 .8 0 1.3 0h94.1c6.7 0 12.6 4.1 15 10.4L434 459.2c6 16.1 23.8 24.6 40.1 19.1l48-16c16.8-5.6 25.8-23.7 20.2-40.5s-23.7-25.8-40.5-20.2l-18.7 6.2-25.5-68c-11.7-31.2-41.6-51.9-74.9-51.9H314.2l-9.6-48H368c17.7 0 32-14.3 32-32s-14.3-32-32-32H291.8z"/></svg>',
                                Access: '<svg style="padding:10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M512 256c0 141.4-114.6 256-256 256S0 397.4 0 256S114.6 0 256 0S512 114.6 512 256zM161.5 169.9c-12.2-5.2-26.3 .4-31.5 12.6s.4 26.3 12.6 31.5l11.9 5.1c17.3 7.4 35.2 12.9 53.6 16.3v50.1c0 4.3-.7 8.6-2.1 12.6l-28.7 86.1c-4.2 12.6 2.6 26.2 15.2 30.4s26.2-2.6 30.4-15.2l24.4-73.2c1.3-3.8 4.8-6.4 8.8-6.4s7.6 2.6 8.8 6.4l24.4 73.2c4.2 12.6 17.8 19.4 30.4 15.2s19.4-17.8 15.2-30.4l-28.7-86.1c-1.4-4.1-2.1-8.3-2.1-12.6V235.5c18.4-3.5 36.3-8.9 53.6-16.3l11.9-5.1c12.2-5.2 17.8-19.3 12.6-31.5s-19.3-17.8-31.5-12.6L338.7 175c-26.1 11.2-54.2 17-82.7 17s-56.5-5.8-82.7-17l-11.9-5.1zM256 160c22.1 0 40-17.9 40-40s-17.9-40-40-40s-40 17.9-40 40s17.9 40 40 40z"/></svg>',
                                Cane: '<svg style="padding:10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M176 96c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48zm-8.4 32c-36.4 0-69.6 20.5-85.9 53.1L35.4 273.7c-7.9 15.8-1.5 35 14.3 42.9s35 1.5 42.9-14.3L128 231.6v43.2c0 17 6.7 33.3 18.7 45.3L224 397.3V480c0 17.7 14.3 32 32 32s32-14.3 32-32V390.6c0-12.7-5.1-24.9-14.1-33.9L224 306.7V213.3l70.4 93.9c10.6 14.1 30.7 17 44.8 6.4s17-30.7 6.4-44.8L268.8 166.4C250.7 142.2 222.2 128 192 128H167.6zM128.3 346.8L97 472.2c-4.3 17.1 6.1 34.5 23.3 38.8s34.5-6.1 38.8-23.3l22-88.2-52.8-52.8zM450.8 505.1c5 7.3 15 9.1 22.3 4s9.1-15 4-22.3L358.9 316.1c-2.8 3.8-6.1 7.3-10.1 10.3c-5 3.8-10.5 6.4-16.2 7.9L450.8 505.1z"/></svg>',
                            }

                            let selectedType = $('#shapeSelect').val();
                            let selectedIcon = $('#kt_docs_select2_icon').val();
                            let selectedColor = $('#colorPicker').val();
                            let selectedSize = $('#sizeSelect').val();
                            let selectedPosition = $('#buttonPosition').val();
                            let isWhitelabel = $('#isWhitelabel').val();

                            $("#colorPicker").spectrum({
                                color: selectedColor,
                                preferredFormat: "rgb",
                                change: function (color) {
                                    selectedColor = color.toRgbString();
                                    runPreview();
                                }
                            });

                            var iconSelect = function (item) {
                                if (!item) {
                                    return;
                                }

                                if (!item.id) {
                                    return item.text;
                                }

                                var span = document.createElement('span');
                                var iconName = item.element.getAttribute('data-kt-select2-icon');
                                var template = '';

                                selectedIcon = iconName;

                                selectedIcon = svgIcons[`${iconName}`];
                                template += '<i class="aacb_triggerSVGSelect" >' + svgIcons[`${iconName}`] + '</i>';
                                //template += '<span class="mx-1">' + iconName + '</span>';

                                span.innerHTML = template;
                                return $(span);
                            }

                            $('#kt_docs_select2_icon').select2({
                                templateSelection: iconSelect,
                                minimumResultsForSearch: -1,
                                templateResult: iconSelect
                            });

                            $('#kt_docs_select2_icon, #sizeSelect, #buttonPosition, #shapeSelect, #isWhitelabel').change(function () {
                                selectedIcon = $('#kt_docs_select2_icon').val();
                                selectedType = $('#shapeSelect').val();
                                selectedSize = $('#sizeSelect').val()
                                selectedPosition = $('#buttonPosition').val()
                                isWhitelabel = $('#isWhitelabel').val()
                                runPreview();
                            })

                            function runPreview() {
                                $('.aacb_preview-window__button').html('');
                                $('.aacb_preview-window__button').append(svgIcons[`${$('#kt_docs_select2_icon').val()}`])

                                const btn = $('.aacb_preview-window__button');
                                btn.find('i, svg').removeClass('h-30px')

                                btn.attr('style', '')
                                switch (selectedPosition) {
                                    case "bottom-right":
                                        btn.css({
                                            right: "10px",
                                            bottom: "15px",
                                        })
                                        break;
                                    case "right-center":
                                        btn.css({
                                            right: "10px",
                                            top: "50%",
                                            transform: "translate(0%, -50%)",
                                        })
                                        break;
                                    case "top-right":
                                        btn.css({
                                            right: "10px",
                                            top: "35px",
                                        })
                                        break;
                                    case "bottom-center":
                                        btn.css({
                                            bottom: "15px",
                                            left: "50%",
                                            transform: "translate(-50%, 0%)",
                                        })
                                        break;
                                    case "bottom-left":
                                        btn.css({
                                            left: "10px",
                                            bottom: "15px",
                                        })
                                        break;
                                    case "left-center":
                                        btn.css({
                                            left: "10px",
                                            top: "50%",
                                            transform: "translate(0%, -50%)",
                                        })
                                        break;
                                    case "top-left":
                                        btn.css({
                                            left: "10px",
                                            top: "35px",
                                        })
                                        break;
                                    default:
                                        break
                                }

                                btn.find('svg').css({
                                    height: selectedSize + 'px',
                                    width: selectedSize + 'px',
                                    fill: '#fff',
                                    color: '#fff'
                                })

                                btn.css({
                                    height: selectedSize + 'px',
                                    width: selectedSize + 'px',
                                    borderColor: selectedColor,
                                    background: selectedColor,
                                    borderRadius: selectedType + 'px',
                                })

                            }


                            runPreview();
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>