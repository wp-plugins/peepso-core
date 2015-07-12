<?php

class PeepSoAdminConfigLicense implements PeepSoAjaxCallback
{
    private static $_instance = NULL;

    private function __construct()
    {
    }

    /*
     * return singleton instance
     */
    public static function get_instance()
    {
        if (self::$_instance === NULL)
            self::$_instance = new self();
        return (self::$_instance);
    }


    /*
     * Builds the required flot data set based on the request
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function check_license(PeepSoAjaxResponse $resp)
    {
        if (!PeepSo::is_admin()) {
            $resp->success(FALSE);
            $resp->error(__('Insufficient permissions.', 'peepso'));
            return;
        }


        $input = new PeepSoInput();
        $plugins = $input->post('plugins');
        $response = array();

        if(count($plugins)) {

            foreach ($plugins as $slug => $name) {

                PeepSoLicense::activate_license($slug, $name);

                $response[$slug] = (int)PeepSoLicense::check_license($name, $slug);
            }
        }

        $resp->set('valid', $response);
        PeepSo::log(__METHOD__.'() done');
        $resp->success(TRUE);
    }
}

// EOF