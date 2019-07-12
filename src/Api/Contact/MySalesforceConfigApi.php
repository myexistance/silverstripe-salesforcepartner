<?php

namespace Sunnysideup\SalesforcePartner\Api\Contact;

use ViewableData;
use Config;
use SS_List;
use LiteralField;
use SalesforceDefaultContactField;
use HiddenField;
use CheckboxSetField;

/**
 * returns a bunch of form fields that inform the user about the configuration
 * of the connection and communication with Salesforce.
 */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: upgrade to SS4
  * OLD:  extends Object (ignore case)
  * NEW:  extends ViewableData (COMPLEX)
  * EXP: This used to extend Object, but object does not exist anymore. You can also manually add use Extensible, use Injectable, and use Configurable
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
class MySalesforceContactConfigApi extends ViewableData
{

    /**
     *
     * @var array
     */
    private static $site_wide_fields_to_send_on_creation = [];

    /**
     *
     * @var array
     */
    private static $site_wide_fields_to_send_on_update = [];

    /**
     *
     * @var array
     */
    private static $site_wide_filter_values = [];

    /**
     *
     * @var array
     */
    private static $run_time_fields_to_send_on_creation = [];

    /**
     *
     * @param  array|DataList|string $mixed fields to send for creations
     *
     */
    public static function add_fields_to_send_on_creation($mixed)
    {
        $array = self::mixed_to_array($mixed);

        self::$run_time_fields_to_send_on_creation += $array;
    }

    /**
     *
     * @var array
     */
    private static $run_time_fields_to_send_on_update = [];

    /**
     *
     * @param  array|DataList|string $mixed fields to send for updates
     *
     * @return array
     */
    public static function add_fields_to_send_on_update($mixed)
    {
        $array = self::mixed_to_array($mixed);

        self::$run_time_fields_to_send_on_update += $array;
    }

    /**
     *
     * @var array
     */
    private static $run_time_fields_for_filter = [];

    /**
     *
     * @param  array|DataList|string $mixed fields to send as filters
     *
     * @return array
     */
    public static function add_fields_to_use_for_filter($mixed)
    {
        $array = self::mixed_to_array($mixed);

        self::$run_time_fields_for_filter += $array;
    }


    /**
     *
     * @param  array|DataList|null $mixed fields to send
     *
     * @return array
     */
    public static function get_fields_to_send_on_creation($mixed = null)
    {
        $array = self::mixed_to_array($mixed);

        return array_merge(
            Config::inst()->get('MySalesforceContactConfigApi', 'site_wide_fields_to_send_on_creation'),
            $array,
            self::$run_time_fields_to_send_on_creation
        );
    }

    /**
     *
     * @param  array|DataList|null $mixed fields to send
     *
     * @return array
     */
    public static function get_fields_to_send_on_update($mixed = null)
    {
        $array = self::mixed_to_array($mixed);

        return array_merge(
            Config::inst()->get('MySalesforceContactConfigApi', 'site_wide_fields_to_send_on_update'),
            $array,
            self::$run_time_fields_to_send_on_update
        );
    }

    /**
     *
     * @param  array|DataList|null $mixed fields to send
     *
     * @return array|DataList|null
     */
    public static function get_fields_for_filter($mixed = null)
    {
        $array = self::mixed_to_array($mixed);

        return array_merge(
            Config::inst()->get('MySalesforceContactConfigApi', 'site_wide_filter_values'),
            $array,
            self::$run_time_fields_for_filter
        );
    }

    /**
     *
     * @param  DataList|array|null|string $mixed
     *
     * @return array
     */
    protected static function mixed_to_array($mixed = null)
    {
        if($mixed === null) {
            $array = [];
        } elseif($mixed instanceof SS_List) {
            $array = [];
            foreach($mixed as $object) {
                $array[trim($object->Key)] = $object->BetterValue();
            }
        } elseif(is_string($mixed)) {
            $array = [ $mixed ];
        } elseif(is_array($mixed)) {
            $array = $mixed;
        } else {
            $array = [];
            user_error('Variable '.print_r($mixed, 1).' should be an array. Currently, it is a '.gettype($mixed));
        }

        return $array;
    }

    public static function login_details_field(
        $title = 'Account Used',
        $fieldName = 'SalesforceLoginDetailsField'
    )
    {
        $userName = Config::inst()->get('MySalesforcePartnerApiConnectionOnly', 'username');
        $password = Config::inst()->get('MySalesforcePartnerApiConnectionOnly', 'password');
        $security_token = Config::inst()->get('MySalesforcePartnerApiConnectionOnly', 'security_token');
        $wsdl_location = Config::inst()->get('MySalesforcePartnerApiConnectionOnly', 'wsdl_location');
        $array = [
            'UserName' => $userName,
            'Password' => substr($password, 0, 2) . '...'.substr($password,  -2),
            'Token' => substr($security_token, 0, 2) . '...'.substr($security_token,  -2),
            'WSDLLocation' => $wsdl_location,
        ];
        return LiteralField::create(
            $fieldName,
            '<h2>'.$title.'</h2>'.
            self::array_to_html($array)
        );
    }
    /**
     * Field with list of contact record types shown
     * @param  string $fieldName
     * @param  string $title
     *
     * @return LiteralField
     */
    public static function list_of_contact_record_types_field(
        $fieldName = 'ListOfContactRecordTypes',
        $title = 'Contact Record Types Available'
    )
    {
        $data = MySalesforceContactApi::retrieve_contact_record_types();

        return LiteralField::create(
            $fieldName,
            '<h2>'.$title.'</h2>'.
            '<pre>'.print_r($data, 1).'</pre>'
        );
    }

    /**
     * Needs to link to a many-many relationship (SalesforceDefaultContactFields)
     * @param  array $array fields to send
     *
     * @return FormField
     */
    public static function select_default_contact_fields_field($fieldName, $title, $desc = '')
    {
        $count = SalesforceDefaultContactField::get()->count();
        if($count === 0) {
            return HiddenField::create(
                $fieldName,
                $title
            );
        }
        return CheckboxSetField::create(
            $fieldName,
            $title,
            SalesforceDefaultContactField::get()->map()->toArray()
        )
            ->setDescription('
                '.$desc.'
                <br />
                You can
                <a href="/admin/salesforceadmin/'.SalesforceDefaultContactField::class.'/">Add or Edit the options</a>
                as required. Please change with care.
            ');
    }

    /**
     *
     * @param  array|DataList|null $array fields to send
     * @param  string $fieldName fields to send
     * @param  string $title fields to send
     *
     * @return LiteralField
     */
    public static function fields_to_send_field(
        $type,
        $mixed = null,
        $fieldName = 'FieldsToSendToSalesforce',
        $title  = 'Default Fields for'
    )
    {
        $fieldName .= ucfirst($type);
        $title .= ' '.ucfirst($type);

        $array = [];
        switch( $type ) {
            case 'create':
                $array = self::get_fields_to_send_on_creation($mixed);
                break;
            case 'update':
                $array = self::get_fields_to_send_on_update($mixed);
                break;
            case 'filter':
                $array = self::get_fields_for_filter($mixed);
                break;
            default:
                user_error('type needs to be create, update or filter - currently set to: '.$type);
                break;
        }
        return LiteralField::create(
            $fieldName,
            '<h2>'.$title.'</h2>'.
            self::array_to_html($array)
        );
    }

    /**
     *
     * @param  array $array
     * @return string
     */
    protected static function array_to_html($array)
    {
        $htmlArray = [];
        foreach($array as $field => $value) {
            $htmlArray[] = $field.' = '.$value;
        }
        if(count($htmlArray) == 0) {
            $htmlArray[] = 'none';
        }

        return '<p>- '.implode('</p><p> - ', $htmlArray).'</p>';
    }
}