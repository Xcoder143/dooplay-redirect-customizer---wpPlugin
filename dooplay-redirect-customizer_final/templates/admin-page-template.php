<div class="wrap" id="dprc-admin-wrap">
    <h1>DP Redirects — Settings</h1>
    <p>Configure the redirect page timer, appearance, monetization, and background sources.</p>

    <div class="dprc-cols">
        <form method="post" action="options.php" class="dprc-form">
            <?php settings_fields('dprc_settings_group'); ?>

            <div id="dprc-tabs">
                <ul>
                    <li><a href="#tab-general">General</a></li>
                    <li><a href="#tab-backgrounds">Backgrounds</a></li>
                    <li><a href="#tab-shorteners">Shorteners</a></li>
                    <li><a href="#tab-monetization">Monetization (Ads & Roles)</a></li>
                </ul>

                <div id="tab-general">
                    <table class="form-table">
                        <tr>
                            <th>Timer (seconds)</th>
                            <td>
                                <input type="number" id="dprc_timeout" name="dprc_options[timeout]" value="<?php echo esc_attr($opts['timeout']); ?>" min="0">
                                <p class="description">Base time in seconds for Guests.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Accent Color</th>
                            <td>
                                <input type="text" id="dprc_accent" class="dprc-color" name="dprc_options[accent]" value="<?php echo esc_attr($opts['accent']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th>Timer Behavior</th>
                            <td>
                                <select name="dprc_options[timer_behavior]">
                                    <option value="continue-btn" <?php selected($opts['timer_behavior'],'continue-btn'); ?>>Show "Continue" Button</option>
                                    <option value="auto" <?php selected($opts['timer_behavior'],'auto'); ?>>Auto Redirect</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tab-backgrounds">
                   <table class="form-table">
                        <tr>
                            <th>Enable Default</th>
                            <td><input type="checkbox" name="dprc_options[default_enable]" value="1" <?php checked($opts['default_enable'],1); ?>></td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>
                                <select name="dprc_options[default_type]">
                                    <option value="image" <?php selected($opts['default_type'],'image'); ?>>Image/GIF</option>
                                    <option value="video" <?php selected($opts['default_type'],'video'); ?>>Video</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>URL</th>
                            <td>
                                <input type="text" id="dprc_default_url" name="dprc_options[default_url]" value="<?php echo esc_attr($opts['default_url']); ?>" class="widefat">
                                <button class="button dprc-upload-btn" data-target="#dprc_default_url">Upload File</button>
                            </td>
                        </tr>
                    </table>
                     <hr>
                    <h3>Fallback Background</h3>
                    <table class="form-table">
                        <tr>
                            <th>Enable Fallback</th>
                            <td><input type="checkbox" name="dprc_options[fallback_enable]" value="1" <?php checked($opts['fallback_enable'],1); ?>></td>
                        </tr>
                         <tr>
                            <th>Type</th>
                            <td>
                                <select name="dprc_options[fallback_type]">
                                    <option value="image" <?php selected($opts['fallback_type'],'image'); ?>>Image/GIF</option>
                                    <option value="video" <?php selected($opts['fallback_type'],'video'); ?>>Video</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>URL</th>
                            <td>
                                <input type="text" id="dprc_fallback_url" name="dprc_options[fallback_url]" value="<?php echo esc_attr($opts['fallback_url']); ?>" class="widefat">
                                <button class="button dprc-upload-btn" data-target="#dprc_fallback_url">Upload File</button>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tab-shorteners">
                    <table class="form-table">
                        <tr>
                            <th>Enable Shorteners</th>
                            <td>
                                <input type="checkbox" name="dprc_options[shorteners_enable]" value="1" <?php checked(isset($opts['shorteners_enable']) ? $opts['shorteners_enable'] : 0, 1); ?>>
                            </td>
                        </tr>
                        <tr>
                            <th>Service URLs</th>
                            <td>
                                <textarea name="dprc_options[shorteners]" rows="7" class="widefat" placeholder="e.g. http://short.com/api?url={{url}}"><?php echo esc_textarea(isset($opts['shorteners']) ? $opts['shorteners'] : ''); ?></textarea>
                                <p class="description">One URL per line. Used for Guests and Members.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tab-monetization">
                    <h3>Role-Based Logic</h3>
                    <p>If enabled, Admins get NO timer/ads. Contributors get 5s timer + No Ads. Subscribers get Half timer + Less Ads.</p>
                    <table class="form-table">
                        <tr>
                            <th>Enable Logic</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="dprc_options[role_logic_enable]" value="1" <?php checked(isset($opts['role_logic_enable']) ? $opts['role_logic_enable'] : 0, 1); ?>>
                                    Active Role-Based Timers & Ads
                                </label>
                            </td>
                        </tr>
                    </table>
                    <hr>
                    <h3>Ad Placements</h3>
                    <p>Paste your HTML/JS Ad codes here.</p>
                    <table class="form-table">
                        <tr>
                            <th>Top Ad (728x90)</th>
                            <td>
                                <textarea name="dprc_options[ad_code_top]" rows="4" class="widefat" placeholder="<script>...</script>"><?php echo esc_textarea(isset($opts['ad_code_top']) ? $opts['ad_code_top'] : ''); ?></textarea>
                                <p class="description">Appears above the movie card.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Bottom Ad (300x250)</th>
                            <td>
                                <textarea name="dprc_options[ad_code_bottom]" rows="4" class="widefat" placeholder="<script>...</script>"><?php echo esc_textarea(isset($opts['ad_code_bottom']) ? $opts['ad_code_bottom'] : ''); ?></textarea>
                                <p class="description">Appears below the Continue button.</p>
                            </td>
                        </tr>
                    </table>
                </div>

            </div> <?php submit_button(); ?>
        </form>

        <div class="dprc-sidebar">
            <div class="dprc-preview-box">
                <h3>Live Preview</h3>
                <p>A simple preview of your accent color.</p>
                <div id="dprc-preview"></div>
            </div>
        </div>

    </div>
</div>