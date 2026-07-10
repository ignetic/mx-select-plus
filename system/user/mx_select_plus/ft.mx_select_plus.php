<?php

/**
 *  MX Select Plus Class for ExpressionEngine
 *
 * @package  ExpressionEngine
 * @subpackage Fieldtypes
 * @category Fieldtypes
 * @author    Max Lazar <max@eecms.dev>
 * @copyright Copyright (c) 2020 Max Lazar
 * @license
 */

class Mx_select_plus_ft extends EE_Fieldtype
{
    /**
     * Fieldtype Info
     *
     * @var array
     */

    public $info = array(
        'name'     => MX_SELECT_NAME,
        'version'  => MX_SELECT_VERSION );

    // Parser Flag (preparse pairs?)
    public $has_array_data = true;

    public $col_id;
    public $row_id;
    public $cell_name;

    private static $js_added         = false;
    private static $cell_bind        = true;
    private static $grid_bind        = true;


    private $ltEE3 = false;

    /**
     * PHP5 construct
     */
    function __construct()
    {
        parent::__construct();
        ee()->lang->loadfile(MX_SELECT_KEY);

        if (defined('APP_VER') && version_compare(APP_VER, '3.0.0', '<')) {
            $this->ltEE3 = true;
        }

    }

    /**
     * Specify compatibility.
     *
     * @param string $name
     *
     * @return bool
     */
    public function accepts_content_type($name)
    {
        $compatibility = array(
        'low_variables',
        'channel',
        'fluid_field',
        'grid',
        'bloqs/1',
        'blocks/1'
        );

        return in_array($name, $compatibility, false);
    }

    // --------------------------------------------------------------------

    /**
     * validate function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function validate($data)
    {
        $valid = true;

    }

    // --------------------------------------------------------------------

    /**
     * display_field function.
     *
     * @access public
     * @param mixed   $field_data
     * @return void
     */
    public function display_field($field_data, $view_type = 'field', $settings = array(), $cp = true, $passed_init = array())
    {
        ee()->load->helper('custom_field');

        $js            = "";
        $r             = "";
        $field_options = array();
        $cell_type     = false;
        $cell          = ($view_type != 'field') ? true : false;
        $field_id      = $this->field_id;
        $field_name_id = str_replace(array( "[", "]" ), "_", $this->field_name);
        $field_class   = $this->field_name;
        $field_name    = $this->field_name;

        $pos                 = strpos($field_name, "[fields]");
        $fluid_field_data_id = (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0;
        $is_fluid_template = (array_key_exists('fluid_field_data_id', $this->settings) && $this->settings['fluid_field_data_id'] == null);

        $col_id = false;
        $new_field_name = $this->field_name.'[new_field][]';

        if ($cell) {
            if (isset($this->settings['grid_field_id'])) {
                // Grid field type
                $field_id = $this->settings['grid_field_id'];
                $this->cell_name = $this->field_name;
                $field_name_id        = 'field_id_'.$field_id.(isset($this->settings['grid_row_id']) ? '_row_id_'.$this->settings['grid_row_id'] : '').'_'.$this->field_name;
                $field_class     = $field_id; //'field_id_'.$this->settings['grid_field_id'].'_'.$this->field_name;
                $cell_type       = 'grid';
                $col_id = $this->field_id;
                $new_field_name = $this->cell_name . '[new_field][]';
            } else {
                // Matrix field type
                $field_name_id = str_replace(array( "[", "]" ), "_", $this->cell_name);
                $field_class = $this->field_name.( ( $cell ) ?  '_col_id_'.$this->col_id : '' );
                $cell_type   = 'matrix';
                $col_id = $this->col_id;
                $new_field_name = $this->cell_name.'[new_field][]';
            }
        }

        // Convert from any previous field change
        $values = is_array($field_data) || is_null($field_data) ? $field_data : str_replace("\n", "|", $field_data);

        $data = array(
            'name'              => ( $cell )  ? $this->cell_name : $field_name,
            'id'                => $field_name_id,
            'value'             => decode_multi_field($values),
            'class'             => rtrim(str_replace(array( "[", "]" ), "_", $field_class), '_'), //$field_class,
            'allow_new_options' => ( $this->settings['allow_new_options'] == 'y' || $this->settings['allow_new_options'] == 'o' ) ?  "true" : "false",
            'allow_deselect'    => ( isset($this->settings['allow_deselect'])) ?  (($this->settings['allow_deselect'] == 'y') ? "true" : "false") : "true"
        );

        if ( $view_type != 'cell' && $view_type != 'grid' && !$is_fluid_template ) {
            $js .='$("#'.$data['id'].'").chosen({no_results_text: "'.lang('no_results').'", add_new_options: '.$data['allow_new_options'].', cell_obj:false, add_new: "'.$new_field_name.'", allow_single_deselect: '.$data['allow_deselect'].', group_class: "#'.$data['id'].'", callback: function() {}});
                    $("div.publish_field.publish_mx_select_plus").css({"overflow-y" : "visible"});
                    $("#low-variables-form").css({"overflow": "visible"});
                    $("#low-variables-form").parents(".pageContents:first").css({"overflow": "visible"});
        ';
        }

        $attr = array (
            ( $this->settings['multiselect'] == 'y' ) ? 'multiple' : '',
            'data-placeholder="' . $this->settings['placeholder'] . '"',
            'class="'.$data['class'].'"',
            'style="' .'width:'.( ( isset($this->settings['min_width']) ) ? $this->settings['min_width'] : "100%" ).';'. '"',
            'id="' . $data['id']. '"',
            'dir="' . $this->_data_help($this->settings, 'field_text_direction') . '"',
            'data-no="'.$data['allow_new_options'].'"',
            'data-deselect="'.$data['allow_deselect'].'"'
        );

        $this->one_time_options($data["value"]);

        // List-type compatibility: prefer 'options', else split 'field_list_items'.
        if (isset($this->settings['options'])) {
            $field_options = (is_array($this->settings['options'])) ? array("" => "") + $this->settings['options'] : array();
        } else {
            $field_options = (isset($this->settings['field_list_items'])) ? preg_split('/\n|\r\n?/', $this->settings['field_list_items']) : array();
            if ($this->settings['multiselect'] === 'y') {
                $field_options = array("" => "") + $field_options;
            }
        }
        if (self::$grid_bind) {
            $js .= "
            (function($) {
                FluidField.on('mx_select_plus', 'add', function(element)
                {
                    var select_field = element.find('select');
                    add_new_options = select_field.data('no');
                    allow_deselect = select_field.data('deselect');
                    var field_id = select_field.attr('id');
                    var field_name = select_field.attr('name');
                    var new_field_name = field_name.slice(0,-2) + '[new_field][]';
                    select_field.chosen({add_new_options: add_new_options , add_new: new_field_name, group_class: '#'+field_id, select_field: element, allow_single_deselect: allow_deselect, callback: function() {}});
                });
            })(jQuery);
            ";
            self::$grid_bind = false;
        }

        if ($cp) {
            $this->_add_js_css($cell_type);
            $this->_insert_js($js);
        }

        // add function for DB
        if (isset($this->settings['db_request']) && !empty($this->settings['db_request'])) {
            if (substr(strtolower(trim($this->settings['db_request'])), 0, 6) == 'select') {
                $optgroup = (strpos(strtolower(trim($this->settings['db_request'])), 'optgroup') === false ) ? false : true ;

                $query = ee()->db->query($this->settings['db_request']);
                if ($query->num_rows() > 0) {
                    foreach ($query->result_array() as $key => $val) {
                        if ($optgroup) {
                            $optgroup = $val['optgroup'];
                            $field_options[$optgroup][$val['option_name']] = $val['option_label'];
                        } else {
                            $field_options[$val['option_name']] = $val['option_label'];
                        }
                    }
                }
            }
        }

        $r .= '<div class="mx-select-plus-wrapper">';

        // Get the existing field data - usually happens when fieldtype has been changed
        if (is_array($data['value']) && !empty($data['value']) && $data['allow_new_options'] == 'true') {
            foreach ($data['value'] as $option) {
                // A value may itself be an array (multi-value); register each.
                if (is_array($option)) {
                    foreach ($option as $value) {
                        if (!isset($field_options[$value])) {
                            $field_options[$value] = $value;
                            $r .= '<input type="hidden" name="' . $new_field_name . '" value="' . $value . '">';
                        }
                    }
                } elseif (!isset($field_options[$option])) {
                    $field_options[$option] = $option;
                    $r .= '<input type="hidden" name="' . $new_field_name . '" value="' . $option . '">';
                }
            }
        }

        $r .= form_dropdown($data['name'].'[]', $field_options, $data["value"], implode(' ', $attr));
        $r .= '</div>';

        return $r;

    }

    // @TODO
    //    The fix is the change eec_matrix_cols > col_settings > change ‘TEXT’ to ‘LONGTEXT’
    //    need to gift TheJae dev lic
    //

    /**
     * one_time_options function.
     *
     * @access public
     * @param mixed   $values
     * @return void
     */
    public function one_time_options($values)
    {
        if ($this->settings['allow_new_options'] != 'o') {
            return;
        }

        foreach ($values as $key) {
            if (!in_array($key, $this->settings['options'])) {
                $this->settings['options'][$key] = $key;
            }
        }

        return;
    }

    /**
     * Displays the cell
     *
     * @access public
     * @param unknown $data The cell data
     */
    public function display_cell($data)
    {

        return $this->display_field($data, 'cell');
    }

    /**
     * Displays grid cell
     *
     * @access public
     * @param unknown $data The cell data
     */
    public function grid_display_field($data)
    {
        return $this->display_field($data, 'grid');
    }

    /**
     * display_var_field function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    public function display_var_field($data)
    {
        return $this->display_field($data);
    }

    /**
     * _add_js_css function.
     *
     * @access private
     * @return void
     */
    function _add_js_css($cell_type = false)
    {
        $theme_url =  URL_THIRD_THEMES . 'mx_select_plus';
        if (!isset(ee()->session->cache[MX_SELECT_KEY]['header'])) {
            ee()->cp->add_to_foot('<script type="text/javascript" src="'.$theme_url . '/js/chosen.jquery.min.js"></script>');
            ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' .$theme_url. '/css/chosen.css" />');
            ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' .$theme_url. '/css/mx_select.css" />');
            ee()->session->cache[MX_SELECT_KEY]['header'] = true;
        };

        if ($cell_type && !isset(ee()->session->cache[MX_SELECT_KEY]['cell_'.$cell_type])) {
            ee()->cp->add_to_foot('<script type="text/javascript" src="' .$theme_url. '/js/mx_select_'.$cell_type.'.js"></script>');
            ee()->session->cache[MX_SELECT_KEY]['cell_'.$cell_type] = true;
        }

    }


    function _sql_wizard()
    {


    }

    /**
     * _get_field_options function.
     *
     * @access private
     * @param mixed   $data
     * @return void
     */
    function _get_field_options($data, $show_empty = '')
    {
        if (! is_array($this->settings['options'])) {
            foreach (explode("\n", trim($this->settings['options'])) as $v) {
                $v = trim($v);

                $field_options[form_prep($v)] = form_prep($v);
            }
        } else {
            $field_options = $this->settings['options'];
        }

        return $field_options;
    }

    /**
     * _insert_js function.
     *
     * @access private
     * @param mixed   $js
     * @return void
     */
    private function _insert_js($js)
    {
        ee()->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
    }



    /**
     * replace_tag function.
     *
     * @access public
     * @param mixed   $data
     * @param string  $params  (default: '')
     * @param string  $tagdata (default: '')
     * @return void
     */
    function replace_tag($data, $params = '', $tagdata = '')
    {
        $r = '';
        $count = 1;

        if (!$tagdata) {
            return $this->replace_ul($data, $params);
        }

        ee()->load->helper('custom_field');

        // Legacy data was newline-delimited; normalise to pipe before decoding.
        $data = (is_array($data) || is_null($data)) ? $data : str_replace("\n", "|", $data);
        $data = decode_multi_field($data);

        // Sort if requested.
        if (isset($params['sort'])) {
            $sort = strtolower($params['sort']);

            if ($sort == 'asc') {
                sort($data);
            } elseif ($sort == 'desc') {
                    rsort($data);
            }
        }

        // process offset and limit parametrs
        if (isset($params['offset']) || isset($params['limit'])) {
            $offset = isset($params['offset']) ? $params['offset'] : 0;
            $limit = isset($params['limit']) ? $params['limit'] : count($data);
            $data = array_splice($data, $offset, $limit);
        }

        if (!isset($params['all_options'])) {
            foreach ($data as $option) {
                $tagdata_tmp = ee()->TMPL->swap_var_single('option', $option, $tagdata);
                $tagdata_tmp = ee()->TMPL->swap_var_single('count', $count, $tagdata_tmp);

                if (isset($this->settings['options'][$option])) {
                    $tagdata_tmp = ee()->TMPL->swap_var_single('option_name', $this->settings['options'][$option], $tagdata_tmp);
                } else {
                    $tagdata_tmp = ee()->TMPL->swap_var_single('option_name', $option, $tagdata_tmp);
                }

                $r .= $tagdata_tmp;

                $count++;
            }

        } else {

            foreach ($this->settings['options'] as $key => $val) {

                $selected = ( in_array($key, $data) ) ? 1 : 0;

                $tagdata_tmp = ee()->TMPL->swap_var_single('option', $key, $tagdata);

                $tagdata_tmp = ee()->TMPL->swap_var_single('option_name', $this->settings['options'][$key], $tagdata_tmp);

                $tagdata_tmp = ee()->TMPL->swap_var_single('selected', $selected, $tagdata_tmp);

                $tagdata_tmp = ee()->TMPL->swap_var_single('count', $count, $tagdata_tmp);

                $r .= $tagdata_tmp;

                $count++;
            }

        }

        $r = ee()->TMPL->swap_var_single('total_results', count($data), $r);

        if (isset($params['backspace'])) {
            $r = substr($r, 0, -$params['backspace']);
        }


        return $r;
    }

    /**
     * replace_ul function.
     *
     * @access public
     * @param mixed   $data
     * @param array   $params (default: array())
     * @return void
     */
    public function replace_ul($data, $params = array())
    {
        return "<ul>"."\n" . $this->replace_tag($data, $params, "<li>{option}</li>"."\n") . '</ul>';
    }

    /**
     * replace_ul function.
     *
     * @access public
     * @param mixed   $data
     * @param array   $params (default: array())
     * @return void
     */
    public function replace_ol($data, $params = array())
    {
        return "<ol>"."\n" . $this->replace_tag($data, $params, "<li>{option}</li>"."\n") . '</ol>';
    }

    /**
     * Display Cell Settings
     *
     * @access public
     * @param unknown $cell_settings array The cell settings
     * @return array Label and form inputs
     */
    public function display_cell_settings($cell_settings)
    {
        return $this->_build_settings($cell_settings, 'matrix');
    }


    /**
     * display_settings function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    public function display_settings($data)
    {
        if ($this->ltEE3) {
            foreach ($this->_build_settings($data) as $v) {
                ee()->table->add_row($v);
            }
        } else {
            return $this->_build_settings($data);
        }
    }

    /**
     * Display Grid Cell Settings
     * @param Array $data Cell settings
     * @return Array Multidimensional array of setting name, HTML pairs
     */
    function grid_display_settings($data)
    {
        $settings = $this->display_settings($data);
        $grid_settings = array();
        foreach ($settings as $value) {
            $grid_settings[$value['label']] = $value['settings'];
        }
        return $grid_settings;
    }

    /**
     * display_var_settings function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    public function display_var_settings($data)
    {
        return $this->_build_settings($data, 'var');
    }


    /**
     * build_settings function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function _build_settings($data, $type = false)
    {
        if ($type == "var") {
            $prefix = 'variable_settings['.MX_SELECT_KEY.']';
        } else {
            $prefix = MX_SELECT_KEY . '_';
        }

        if ($this->ltEE3 || $type == "var" || $type == "matrix") {
        //variable_settings
            return array (
                array( lang('placeholder', 'placeholder'), form_input($prefix . '[placeholder]', $this->_data_help($data, 'placeholder')) ),
                array( lang('multiselect', 'multiselect'), form_dropdown($prefix . '[multiselect]', array( 'y' => lang('yes'), 'n' => lang('no') ), $this->_data_help($data, 'multiselect', 'n')) ),
                array( lang('allow_new_options', 'allow_new_options'), form_dropdown($prefix . '[allow_new_options]', array( 'y' => lang('yes'), 'n' => lang('no'), 'o' => lang('one_time') ), $this->_data_help($data, 'allow_new_options', 'n')) ),
                array( lang('allow_deselect', 'allow_deselect'), form_dropdown($prefix . '[allow_deselect]', array( 'y' => lang('yes'), 'n' => lang('no') ), $this->_data_help($data, 'allow_deselect', 'y')) ),
                //array( lang( 'source', 'source' ), form_dropdown( $prefix . '[source]', array( 'stadart_list' => lang( 'stadart_list' ), 'db' => lang( 'db' ), 'json' => lang( 'json' ) ), $this->_data_help( $data, 'source', 'stadart_list' ) ) ),
                array( lang('min_width', 'min_width'), form_input($prefix . '[min_width]', $this->_data_help($data, 'min_width', '300px')) ),
                array( lang('field_list_items', 'field_list_items'), form_textarea($prefix . '[options]', $this->_options($this->_data_help($data, 'options'))) ),

                array( lang('db_request', 'db_request'), form_textarea($prefix . '[db_request]', $this->_data_help($data, 'db_request')) )

            );
        } else {

            // list type compatibility - move from 'options' to 'field_list_items'
            if (isset($data['options'])) {
                $options = $this->_options($this->_data_help($data, 'options'));
            } else {
                $options = $this->_data_help($data, 'field_list_items');
            }

            $fields['placeholder'][$prefix.'[placeholder]'] = array(
                'type' => 'text',
                'value' => $this->_data_help($data, 'placeholder'),
            );
            $fields['multiselect'][$prefix.'[multiselect]'] = array(
                'type' => 'select',
                'choices' => array( 'y' => lang('yes'), 'n' => lang('no') ),
                'value' => $this->_data_help($data, 'multiselect', 'n'),
            );
            $fields['allow_new_options'][$prefix.'[allow_new_options]'] = array(
                'type' => 'select',
                'choices' => array( 'y' => lang('yes'), 'n' => lang('no'), 'o' => lang('one_time') ),
                'value' => $this->_data_help($data, 'allow_new_options', 'n'),
            );
            $fields['allow_deselect'][$prefix.'[allow_deselect]'] = array(
                'type' => 'select',
                'choices' => array( 'y' => lang('yes'), 'n' => lang('no') ),
                'value' => $this->_data_help($data, 'allow_deselect', 'n'),
            );
            $fields['min_width'][$prefix.'[min_width]'] = array(
                'type' => 'text',
                'value' => $this->_data_help($data, 'min_width', '300px'),
            );
            $fields['field_list_items'][$prefix.'[options]'] = array(
                'type' => 'textarea',
                'value' => $options,
            );
            $fields['db_request'][$prefix.'[db_request]'] = array(
                'type' => 'textarea',
                'value' => $this->_data_help($data, 'db_request'),
            );

            $settings = array();
            foreach ($fields as $key => $val) {
                $settings[] = array(
                    'title' => $key,
                    'desc' => '',
                    'fields' => $val
                );
            }

            return array('field_options_mx_select_plus' => array(
                'label' => 'field_options',
                'group' => 'mx_select_plus',
                'settings' => $settings
            ));

        }

        //
    }

    /**
     * _data_help function.
     *
     * @access private
     * @param mixed   $data
     * @param string  $default (default: '')
     * @return void
     */
    function _data_help($data, $key, $default = '')
    {
        return ( empty($data[$key]) or $data[$key] == '' ) ? $default : $data[$key];
    }

    /**
     * _data_help function.
     *
     * @access private
     * @param mixed   $data
     * @param mixed   $key
     * @param string  $default (default: '')
     * @return void
     */
    function _options($options = array())
    {
        $r = '';

        if (!is_array($options)) {
            return $r;
        }

        foreach ($options as $name => $label) {

            //needs to rewrite this block
            if (is_array($label)) {
                if ($r !== '') {
                        $r .= "\n";
                }
                $r .= '[['.$name.']]';

                foreach ($label as $n => $l) {
                    if ($r !== '') {
                        $r .= "\n";
                    }

                    if (!$n && !$l) {
                        $n = $l = ' ';
                    }

                    $r .= htmlspecialchars($n);

                    if ($n != $l) {
                        $r .= ' : '.$l;
                    }
                }

            } else {

                if ($r !== '') {
                    $r .= "\n";
                }

                if (!$name && !$label) {
                    $name = $label = ' ';
                }

                $r .= htmlspecialchars($name);

                if ($name != $label) {
                    $r .= ' : '.$label;
                }
            }
        }

        return $r;

    }
    /**
     * save_cell_settings function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function save_cell_settings($data)
    {

        return $this->save_settings($data);

    }

    /**
     * save_var_settings function.
     *
     * @access public
     * @param mixed   $var_settings
     * @return void
     */
    function save_var_settings($var_settings)
    {

        return $this->save_settings($var_settings, 'var');

    }

    /**
     * grid_save_settings function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function grid_save_settings($data)
    {
        return $this->save_settings($data);
    }

    /**
     * save_settings function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function save_settings($data, $type = false)
    {
        $pattern = '#'.'\[\['.'(.*?)' .'\]\]'.'#s';
        $current_optgroup = false;

        $prefix = MX_SELECT_KEY . '_';

        $vars = array();
        if ($this->ltEE3) {
            $vars = $data;
        }

        if ($type == "var") {
            $data[$prefix] = $data;
        }

        if (isset($data[$prefix])) {

            // list type compatibility
            if (isset($data[$prefix]['options'])) {
                $vars['field_list_items'] = $data[$prefix]['options'];
            }

            foreach ($data[$prefix] as $key => $val) {

                if ($key == "options") {

                    $out = array();
                    foreach (explode("\n", $val) as $option) {


                        // check for optgroups
                        if (is_string($option)
                          && preg_match($pattern, $option, $matches)
                        ) {
                            $optgroup = $matches[1];
                            $current_optgroup = $optgroup;
                        } else {
                            $value_name = explode(" : ", $option, 2);

                            if (!$current_optgroup) {
                                $out[$value_name[0]] = isset($value_name[1]) ? $value_name[1] : $value_name[0];
                            } else {
                                $out[$current_optgroup][$value_name[0]] = isset($value_name[1]) ? $value_name[1] : $value_name[0];
                            }

                        }

                    }
                    $val = $out;

                }

                $vars[$key] = $val;

            }

        }

        return $vars;

    }
    // --------------------------------------------------------------------


    // --------------------------------------------------------------------
    /**
     * install function.
     *
     * @access public
     * @return void
     */
    function install()
    {
        return array(
            '' => ''
        );

    }

    /**
     * save function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function save($data)
    {
        // fluid fields - need to remove default string value
        if (!is_array($data)) {
            $data = '';
        }

        $data = $this->save_options($data);

        if (!empty($data)) {
            ee()->load->helper('custom_field');
            // encode_multi_field escapes literal pipes, matching how native
            // list fieldtypes (Select/Checkboxes) store multi-values.
            $data = ( is_array($data) ) ? encode_multi_field($data) : $data;
        } else {
            $data = $data;
        }

        return $data;
    }


    /**
     * save_var_field function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function save_var_field($data)
    {
        return $this->save($data);
    }


    /**
     * save_cell function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function save_cell($data)
    {
        if (!is_array($data)) {
            return;
        }

        return $this->save($data);
    }

    /**
     * save_options function.
     *
     * @access public
     * @param mixed   $data
     * @return void
     */
    function save_options($data)
    {
        if (!isset($this->field_id)) {
            return $data;
        }

        $type = false;
        $col_id = false;
        $field_id = $this->field_id;

        // Low/Pro Variables
        if (isset($this->var_id)) {

            $type = 'var';
            $field_id = $this->field_name;

        // GRID
        } elseif (isset($this->settings['grid_field_id'])) {

            $field_id = $this->settings['grid_field_id'];
            $col_id = $this->field_id;

        // MATRIX
        } elseif ($this->settings['field_type'] === 'matrix') {

            $field_id = $this->field_id;
            $col_id = $this->settings['col_id'];

        }

        if (isset($data['new_field'])) {

            $this->update_settings_live($data['new_field'], $field_id, $col_id, $type);
            unset($data['new_field']);
        }

        return $data;
    }



    /**
     * update_settings_live function.htmlspecialchars
     *
     * @access public
     * @param mixed   $data
     * @param mixed   $field_id
     * @param bool    $type     (default: false)
     * @return void
     */
    function update_settings_live($data, $field_id, $col_id = false, $type = false)
    {
        $field_type = 'mx_select_plus';

        if (!$type) {
            $field_query = ee()->db->select('field_type')->from('channel_fields')->where('field_id', $field_id)->get()->row('field_type');

            if (!empty($field_query)) {
                $field_type = $field_query;
            }

            if ($field_type == 'grid') {

                ee()->db->select('col_settings');
                ee()->db->where('field_id', $field_id);
                ee()->db->where('col_id', $col_id);
                $query = ee()->db->get('grid_columns');

                if ($query->num_rows() > 0) {
                    $col_settings = json_decode($query->row()->col_settings, true);

                    if ($col_settings['allow_new_options'] != 'y') {
                        return;
                    }

                    foreach ($data as $key => $val) {
                        $col_settings['options'][$val] = $val;
                    }

                    ee()->db->where('field_id', $field_id);
                    ee()->db->where('col_id', $col_id);
                    ee()->db->set('col_settings', json_encode($col_settings));
                    ee()->db->update('grid_columns');

                }

            } elseif ($field_type == 'matrix') {

                ee()->db->select('col_settings');
                ee()->db->where('field_id', $field_id);
                ee()->db->where('col_id', $col_id);
                $query = ee()->db->get('matrix_cols');

                if ($query->num_rows() > 0) {
                    $col_settings = unserialize(base64_decode($query->row()->col_settings));

                    if ($col_settings['allow_new_options'] != 'y') {
                        return;
                    }

                    foreach ($data as $key => $val) {
                        $col_settings['options'][$val] = $val;
                    }

                    ee()->db->where('field_id', $field_id);
                    ee()->db->where('col_id', $col_id);
                    ee()->db->set('col_settings', base64_encode(serialize($col_settings)));
                    ee()->db->update('matrix_cols');

                }

            } else {

                ee()->db->select('field_settings');
                ee()->db->where('field_id', $field_id);
                $query = ee()->db->get('channel_fields');

                if ($query->num_rows() > 0) {
                    $field_settings = unserialize(base64_decode($query->row()->field_settings));

                    if ($field_settings['allow_new_options'] != 'y') {
                        return;
                    }
                    if (isset($field_settings['options'])) {
                        $options = array_values($field_settings['options']);
                        unset($field_settings['options']);
                    } else {
                        $options = preg_split('/\n|\r\n?/', $field_settings['field_list_items']);
                    }

                    $options_list = array_merge($options, $data);
                    $field_settings['field_list_items'] = implode("\n", $options_list);

                    ee()->db->where('field_id', $field_id);
                    ee()->db->set('field_settings', base64_encode(serialize($field_settings)));
                    ee()->db->update('channel_fields');
                }

            }

        } elseif ($type == 'var') {

            $variable_id = false;
            $variable_table = false;

            if (ee()->db->table_exists('pro_variables')) {
                $variable_table = 'pro_variables';
            }
            if (ee()->db->table_exists('low_variables')) {
                $variable_table = 'low_variables';
            }
            if (!$variable_table) {
                return;
            }

            if (isset($this->var_id)) {
                $variable_id = $this->var_id;
            } else {
                $variable_id = ee()->db->select('variable_id')->where('variable_name', $field_id)->get('global_variables')->row('variable_id');
            }

            if ($variable_id) {

                $var_query = $query = ee()->db->select('variable_settings')->where('variable_id', $variable_id)->get($variable_table);

                if ($var_query->num_rows() > 0) {

                    $variable_settings = $var_query->row()->variable_settings;

                    $encoding = 'json';

                    // check old serialize encoding
                    if (substr($variable_settings, 0, 3) == 'YTo') {
                        $variable_settings = str_replace('_', '/', $variable_settings);
                        $variable_settings = @unserialize(base64_decode($variable_settings));
                        $encoding = 'serialize';
                    } else {
                        $variable_settings = json_decode($variable_settings, true);
                    }

                    foreach ($data as $key => $val) {
                        $variable_settings['options'][$val] = $val;
                    }

                    if ($encoding == 'serialize') {
                        ee()->db->set('variable_settings', base64_encode(serialize($variable_settings)));
                    } else {
                        ee()->db->set('variable_settings', json_encode($variable_settings));
                    }

                    ee()->db->where('variable_id', $variable_id);
                    ee()->db->update($variable_table);

                }
            }

        }

    }

    function update($current = '')
    {
        if ($current == $this->info['version']) {
            return false;
        }
        return true;
    }

}

// END mx_select_plus_ft class

/* End of file ft.mx_select_plus.php */
