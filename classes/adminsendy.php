<?php

class PeepSoAdminSendy implements PeepSoAjaxCallback
{
    private static $_instance = NULL;

    private function __construct() {}

    public static function get_instance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    public function add_user(PeepSoAjaxResponse $resp)
    {
        if (!PeepSo::is_admin()) {
            $resp->success(FALSE);
            $resp->error(__('Insufficient permissions.', 'peepso'));
            return;
        }

        $input = new PeepSoInput();
        $sendy_list_id      = $input->post('sendy_list_id');
        $sendy_url          = $input->post('sendy_url');
        $sendy_name         = $input->post('sendy_name');
        $sendy_last_name    = $input->post('sendy_last_name');
        $sendy_email        = $input->post('sendy_email');

        $response= wp_remote_post( $sendy_url, array( 'body' => array( 'name' => $sendy_name, 'last_name' => $sendy_last_name, 'email' => $sendy_email, 'list' => $sendy_list_id,'boolean' => 'true') ) );

        $resp->success( FALSE );

        if( is_wp_error( $response ) ) {
            $resp->error( $response->get_error_message() );
        } else {

            if('1' == $response['body']) {
                $resp->success(TRUE);
            } else {
                $resp->error( $response['body']  );
            }
        }
    }
}
// EOF