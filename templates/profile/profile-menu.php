<ul class="ps-list profile-interactions">
    <?php
    foreach ($links as $priority_number => $links) {
        foreach ($links as $link) {
            ?>
            <li <?php

            if ($current == $link['id']) {
                echo ' class="current" ';
            }

            ?>>
                <a href="<?php echo peepso('profile', 'user_link') . '/' . $link['href'];?>">
                    <?php echo $link['title'];?>
                </a>
            </li>
        <?php
        }
    }
    ?>
</ul>