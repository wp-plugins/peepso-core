<div class="ps-sidebar">

    <?php
    $positions = array(
        'profile_sidebar_top',
        'profile_sidebar_bottom',
    );

    // @TODO Still D.R.Y.

    foreach($positions as $position) {
        ?>
        <div class="peepso_sidebar_<?php echo $position;?>">
            <?php

            $widgets=$$position;

            if(sizeof($widgets)) {
                foreach($widgets as $widget) {
                        $widget['is_profile_widget'] = TRUE;
                        the_widget($widget['widget_class'], $widget);
                    }
            }
            ?>
        </div>
    <?php } ?>
</div>
<?php
// EOF