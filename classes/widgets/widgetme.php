<?php


class PeepSoWidgetMe extends WP_Widget
{

    /**
     * Set up the widget name etc
     */
    public function __construct($id = null, $name = null, $args= null) {
        if(!$id)    $id     = 'PeepSoWidgetMe';
        if(!$name)  $name   = __( 'PeepSo Profile', 'peepsomewidget' );
        if(!$args)  $args   = array( 'description' => __( 'PeepSo Profile Widget', 'peepsomewidget' ), );

        parent::__construct(
            $id, // Base ID
            $name, // Name
            $args // Args
        );
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {

        $instance['user_id']    = get_current_user_id();
        $instance['user']       = new PeepSoUser($instance['user_id']);

        // Disable the widget for guests if
        if(isset($instance['guest_behavior']) && 'hide' === $instance['guest_behavior'] && !$instance['user_id'])
        {
            return FALSE;
        }

        // List of links to be displayed
        $links = array();
        $links = apply_filters('peepso_widget_me_links', $links);


        $instance['links'] = $links;

        if(!array_key_exists('template', $instance) || !strlen($instance['template']))
        {
            $instance['template'] = 'me.tpl';
        }

        PeepSoTemplate::exec_template( 'widgets', $instance['template'], array( 'args'=>$args, 'instance' => $instance ) );
    }

    /**
     * Outputs the admin options form
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {

        $instance['fields'] = array(
            // general
            'section_general' => FALSE,
            'limit'     => FALSE,
            'title'     => FALSE,

            // peepso
            'integrated'   => FALSE,
            'position'  => FALSE,
            'ordering'  => FALSE,
            'hideempty' => FALSE,

        );

        ob_start();

        $guest_behavior = !empty($instance['guest_behavior']) ? $instance['guest_behavior'] : 'login';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('guest_behavior'); ?>">
                <?php _e('Guest view', 'peepso'); ?>
                <select class="widefat" id="<?php echo $this->get_field_id('guest_behavior'); ?>"
                        name="<?php echo $this->get_field_name('guest_behavior'); ?>">
                    <option value="login"><?php _e('Log-in form', 'peepso'); ?></option>
                    <option value="hide" <?php if('hide' === $guest_behavior) echo ' selected="selected" ';?>><?php _e('Hide', 'peepso'); ?></option>
                </select>

            </label>
        </p>
        <?php
        $html = ob_get_clean();

        #$this->instance = $instance;

        $settings =  apply_filters('peepso_widget_form', array('html'=>$html, 'that'=>$this,'instance'=>$instance));
        echo $settings['html'];
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['guest_behavior'] = $new_instance['guest_behavior'];
        #$instance['title']          = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }
}

// EOF