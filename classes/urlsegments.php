<?php

/**
 * Used to retrieve a URL segment found after the current page where the shortcode is locationed.
 *
 * Examples:
 * When run on page: http://domain.com/profile/username
 * ->get(0) returns 'peepso_profile'
 * ->get(1) returns 'username'
 *
 * When run on page: http://domain.com/community/yours/groups/public-group-name/members
 * ->get(0) returns 'peepso_groups'
 * ->get(1) returns 'public-group-name'
 * ->get(2) reutrns 'members'
 */

class PeepSoUrlSegments
{
    public $_segments = NULL;
    public $_shortcode = NULL;

    public function __construct()
    {
        if ( NULL === $this->_segments ) {
            global $wp_query;
           # if ($wp_query->is_404) {

            $subfolder=trim(str_replace(array('https://','http://',$_SERVER['HTTP_HOST']),'',site_url()),'/').'/';

                $page = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // $wp_query->query['pagename'];

                // @TODO
                if(strlen($subfolder)>1) {
                    $page = substr($page, strlen($subfolder));
                }

                $i = 0;
                while( strlen($page) > 1 ) {



                    // Do not remove the last segment in the first iteration
                    if($i++>0) {
                        $page = dirname($page);
                    }

                    // Infinite loop
                    if($i > 100) {
                        die();
                    }

                    $args = array(
                        'pagename' => $page,
                        'post_type' => 'page',
                        'posts_per_page' => 1,
                    );

                    $search_page = new WP_Query($args);

                    if (!$search_page->is_404 && $search_page->post_count > 0) {

                        // verify that there's a PeepSo shortcode on the page
                        if (FALSE !== strpos($search_page->posts[0]->post_content, '[peepso')) {

                            // reset the global $wp_query to the page with the peepso shortcode
                            if($wp_query->is_404) {
                                $wp_query = $search_page;
                                global $post;
                                $post = $wp_query->posts[0];
                            }



                            // detect the shortcode on the page
                            $pattern = get_shortcode_regex();

                            if (preg_match_all('/' . $pattern . '/s', $search_page->posts[0]->post_content, $matches)) {

                                foreach ($matches as $idx => $shortcodes) {
                                    $shortcode = $shortcodes[0];

                                    // check for a PeepSo shortcode
                                    if ('[peepso' === substr($shortcode, 0, 7) && ']' === substr($shortcode, -1)) {
                                        $this->_shortcode = trim($shortcode, '[]');
                                        return;
                                    }
                                }
                            }
                        }
                        return;
                    }
                }
            #}
        }
    }

    /**
     * Returns the URL segment indicated
     * @param int $idx The index into the URL structure, starting with the URL segment that contains the shortcode used on the page.
     * @return string A string containing the requeste URL segment.
     */
    public function get($idx = 1)
    {
        $this->parse_segments();

        // range check
        if ($idx < 0 || $idx >= count($this->_segments)) {
            return ('');
        }

        return ($this->_segments[$idx]);
    }

    public function get_segments()
    {
        return $this->_segments;
    }

    /**
     * Internal method used to parse the URL segments into an array for later retrieval.
     * @return type
     */
    public function parse_segments()
    {
        // if it's already been parsed, no need to do it again
        if (NULL !== $this->_segments)
            return;

        // get the permalink for the page
        $permalink = get_permalink();
        $permalink_path = parse_url($permalink, PHP_URL_PATH);

        // parse the URL segments
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        // remove the permalink prefix ... this allows [peepso_*] shortcodes on any depth path
        if (substr($url, 0, strlen($permalink_path)) === $permalink_path)
            $url = substr($url, strlen($permalink_path));


        $path = '/' . trim(parse_url($url, PHP_URL_PATH), '/');

        $this->_segments = explode('/', $path);
        $this->_segments[0] = $this->_shortcode; // PeepSo::get_current_shortcode();
    }

    public static function get_view_id($login)
    {
        if ($login && strlen($login))
        {
            if(is_int($login))
            {
                $user = get_user_by('id', $login);
            }
            else
            {
                $user = get_user_by('login', $login);
            }

            if (FALSE === $user)
            {
                $view_user_id = PeepSo::get_user_id();
            }
            else
            {
                $view_user_id = $user->ID;
            }
        }
        else
        {
            $view_user_id = PeepSo::get_user_id();
        }

        return $view_user_id;
    }
}

// EOF