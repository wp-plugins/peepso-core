<?php

echo $args['before_widget'];

?>

<div class="ps-module-profile">

<?php
if($instance['user_id'] >0)
{
    $user  = $instance['user'];
    ?>

    <div class="ps-module-profile-header">
        <!-- Avatar -->
        <div class="ps-avatar">
            <a href="<?php echo $user->get_profileurl();?>">
                <img alt="<?php echo $user->get_profileurl();?>" title="<?php echo $user->get_profileurl();?>" src="<?php echo $user->get_avatar();?>">
            </a>
        </div>

        <!-- Name, edit profile -->
        <div class="ps-module-profile-info">
            <a class="ps-user-name" href="<?php echo $user->get_profileurl();?>">
                <?php echo $user->get_display_name();?>
            </a>
            <a href="<?php echo PeepSo::get_page('profile');?>?edit">
                <?php _e('Edit Profile', 'peepso');?>
            </a>
        </div>
    </div>

    <!-- Links -->
    <ul class="ps-list">
        <?php
        foreach($instance['links'] as $priority_number => $links)
        {
            foreach($links as $link) {
                echo '<li class="ps-list-item"><a href="' . $link['href'] . '"><span class="' . $link['icon'] . '"></span> ' . $link['title'] . '</a></li>';
            }
        }
        ?>
    </ul>
<?php
    } else {
?>
    <form class="ps-form" action="" onsubmit="return false;" method="post" name="login" id="form-login-me">
        <div class="ps-form-row">
            <div class="ps-form-controls ps-form-input-icon">
                <span class="ps-icon"><i class="ps-icon-user"></i></span>
                <input class="ps-input full" type="text" name="username" id="username" placeholder="<?php _e('Username', 'peepso'); ?>" mouseev="true"
                    autocomplete="off" keyev="true" clickev="true" />
            </div>
            <div class="ps-form-controls ps-form-input-icon">
                <span class="ps-icon"><i class="ps-icon-lock"></i></span>
                <input class="ps-input full" type="password" name="password" id="password" placeholder="<?php _e('Password', 'peepso'); ?>" mouseev="true"
                            autocomplete="off" keyev="true" clickev="true" />
            </div>
            <div class="ps-form-controls ps-checkbox">
                <input type="checkbox" alt="<?php _e('Remember Me', 'peepso'); ?>" value="yes" id="remember" name="remember">
                <span><?php _e('Remember Me', 'peepso'); ?></span>
            </div>
            <div class="ps-form-controls">
                <a class="ps-link block" href="<?php peepso('page-link', 'recover'); ?>"><?php _e('Recover Password', 'peepso'); ?></a>
                <a class="ps-link block" href="<?php peepso('page-link', 'register'); ?>?resend"><?php _e('Resend activation code', 'peepso'); ?></a>
            </div>
            <button type="submit" id="login-submit" class="ps-btn ps-btn-login">
                <span><?php _e('Login', 'peepso'); ?></span>
                <img style="display:none" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
            </button>
        </div>

        <input type="hidden" name="option" value="ps_users">
        <input type="hidden" name="task" value="-user-login">
    </form>
    <div style="display:none">
        <form name="loginform" id="loginform" action="<?php peepso('page-link', 'home'); ?>wp-login.php" method="post">
            <input type="text" name="log" id="user_login" />
            <input type="password" name="pwd" id="user_pass" />
            <input type="checkbox" name="rememberme" id="rememberme" value="forever" />
            <input type="submit" name="wp-submit" id="wp-submit" value="Log In" />
            <input type="hidden" name="redirect_to" value="<?php peepso('redirect-login'); ?>" />
            <input type="hidden" name="testcookie" value="1" />
            <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
        </form>
    </div>

<script>
jQuery(function( $ ) {
    $('#form-login-me').on('submit', function( e ) {
        e.preventDefault();
        e.stopPropagation();
        ps_login.form_submit( e );
    });
});
</script>

<?php
    }
?>

</div>

<?php
echo $args['after_widget'];
// EOF