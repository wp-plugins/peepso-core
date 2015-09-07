<style type="text/css">
    .widget_integrated_description {
        color: #666666;
        font-size:11px;
        font-style: italic;
        text-align:justify;
    }
</style>
<?php
#$instance   = $widget['instance'];  // widget settings array
#$that       = $widget['that'];      // the object itself

// general
if(!isset($instance['fields']['section_general']) || TRUE === $instance['fields']['section_general'])
{
    echo '<h3>' . __('General settings', 'peepso') . '</h3>';
}

// general.title
if(isset($instance['fields']['title']) && TRUE === $instance['fields']['title'])
{
    $title = !empty($instance['title']) ? $instance['title'] : '';
    ?>
    <p>
        <label for="<?php echo $that->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
        <input class="widefat" id="<?php echo $that->get_field_id('title'); ?>"
               name="<?php echo $that->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
    </p>


<?php
}


// general.limit
if(isset($instance['fields']['limit']) && TRUE === $instance['fields']['limit'])
{
    $limit = ! empty( $instance['limit'] ) ? $instance['limit'] : 12;
    ?>
    <p>
        <label for="<?php echo $that->get_field_id( 'limit' ); ?>"><?php _e( 'Limit:', 'peepso'); ?></label>
        <select class="widefat" id="<?php echo $that->get_field_id( 'limit' ); ?>" name="<?php echo $that->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            <?php

            $options = array(1,2,3,4,5,6,7,8,9,10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30);

            foreach($options as $option)
            {
                ?>
                <option value="<?php echo $option;?>" <?php if($option==$limit) echo " selected ";?> ><?php echo $option;?></option>
            <?php
            }
            ?>
        </select>
    </p>
<?php
}




// Widgetized PeepSo
if(isset($instance['fields']['integrated']) && TRUE === $instance['fields']['integrated'])
{
    echo '<h3>' . __('PeepSo Integrated Widget', 'peepso') . '</h3>';
?>
    <p class="widget_integrated_description">
            <?php _e('Options below only  take effect if the widget is published in "PeepSo" widget area.', 'peepso');?>
    </p>
<?php
// widgetize.hideempty
    if (isset($instance['fields']['hideeempty']) && TRUE === $instance['fields']['hideempty'])
    {
        $hideempty = !empty($instance['hideempty']) ? $instance['hideempty'] : 0;
        ?>
        <p>
            <label for="<?php echo $that->get_field_id('hideempty'); ?>">
                <input <?php if (1 === $hideempty) echo ' checked="checked" ';?> value="1" type="checkbox"
                                                                                 name="<?php echo $that->get_field_name('hideempty');?>"
                                                                                 id="<?php echo $that->get_field_id('hideempty');?>">
                <?php _e('Hide when empty', 'peepso'); ?>
            </label>
        </p>
    <?php
    }



// widgetize.position
    if(isset($instance['fields']['position']) && TRUE === $instance['fields']['position'])
    {
        $position = !empty($instance['position']) ? $instance['position'] : 0;
        $positions = apply_filters('peepso_widget_list_positions', array());
        ?>
        <p>
            <label for="<?php echo $that->get_field_id('position'); ?>"><?php echo __('Position', 'peepso'); ?></label>
            <select class="widefat" id="<?php echo $that->get_field_id('position'); ?>"
                    name="<?php echo $that->get_field_name('position'); ?>">
                <?php
                foreach ($positions as $option)
                {
                    ?>
                    <option
                        value="<?php echo $option; ?>" <?php if ($option === $position) echo ' selected="selected" '; ?>><?php _e($option, 'peepsofriendswidget'); ?></option>
                <?php
                }
                ?>
            </select>
        </p>
    <?php
    }
}