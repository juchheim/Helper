<div class="helper-form-container">
    <form id="login-form" class="active" method="post">
        <h2>Login</h2>
        <p>
            <label for="user_login"><?php _e('Username or Email'); ?><br/>
            <input type="text" name="log" id="user_login" class="input" value="" size="20" /></label>
        </p>
        <p>
            <label for="user_pass"><?php _e('Password'); ?><br/>
            <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>
        </p>
        <?php wp_nonce_field( 'helper_login', 'helper_login_nonce' ); ?>
        <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>" /></p>
        <button type="button" class="toggle-link" id="show-register">Don't have an account? Create one</button>
    </form>
    <form id="register-form" method="post">
        <h2>Register</h2>
        <p>
            <label for="user_login"><?php _e('Username'); ?><br/>
            <input type="text" name="user_login" id="user_login" class="input" value="" size="20" /></label>
        </p>
        <p>
            <label for="user_email"><?php _e('Email'); ?><br/>
            <input type="email" name="user_email" id="user_email" class="input" value="" size="25" /></label>
        </p>
        <p>
            <label for="user_pass"><?php _e('Password'); ?><br/>
            <input type="password" name="user_pass" id="user_pass" class="input" value="" size="25" /></label>
        </p>
        <p>
            <label for="user_role"><?php _e('Role'); ?><br/>
            <select name="user_role" id="user_role" class="input">
                <option value="volunteer">Volunteer</option>
                <option value="organization">Organization</option>
            </select>
        </p>
        <div id="volunteer_fields" style="display:none;">
            <p>
                <label for="first_name"><?php _e('First Name'); ?><br/>
                <input type="text" name="first_name" id="first_name" class="input" value="" size="25" /></label>
            </p>
            <p>
                <label for="last_name"><?php _e('Last Name'); ?><br/>
                <input type="text" name="last_name" id="last_name" class="input" value="" size="25" /></label>
            </p>
            <p>
                <label for="skills"><?php _e('Skills'); ?><br/>
                <textarea name="skills" id="skills" class="input" rows="5"></textarea></label>
            </p>
        </div>
        <div id="organization_fields" style="display:none;">
            <p>
                <label for="organization_name"><?php _e('Organization Name'); ?><br/>
                <input type="text" name="organization_name" id="organization_name" class="input" value="" size="25" /></label>
            </p>
            <p>
                <label for="needs"><?php _e('Needs'); ?><br/>
                <textarea name="needs" id="needs" class="input" rows="5"></textarea></label>
            </p>
        </div>
        <?php wp_nonce_field( 'helper_register', 'helper_register_nonce' ); ?>
        <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Register'); ?>" /></p>
        <button type="button" class="toggle-link" id="show-login">Already have an account? Log In</button>
    </form>
</div>
<script>
    jQuery(document).ready(function($) {
        $('#register-form').hide();
        $('#show-login').hide();

        $('#show-register').click(function() {
            $('#login-form').slideUp(400, function() {
                $('#register-form').slideDown(400).addClass('active');
                $('#show-register').hide();
                $('#show-login').show();
            });
        });

        $('#show-login').click(function() {
            $('#register-form').slideUp(400, function() {
                $('#login-form').slideDown(400).addClass('active');
                $('#show-login').hide();
                $('#show-register').show();
            });
        });

        $('#user_role').change(function() {
            if ($(this).val() == 'volunteer') {
                $('#volunteer_fields').show();
                $('#organization_fields').hide();
            } else if ($(this).val() == 'organization') {
                $('#volunteer_fields').hide();
                $('#organization_fields').show();
            }
        }).change();  // Trigger change to set initial state
    });
</script>
