<?php if (! defined('ABSPATH')) {
    exit;
}
/**
 * flgf_gravityform_labels_activate()
 *
 * Plugin Activation.
 */
if (! function_exists('flgf_gravityform_labels_activate')) {
    function flgf_gravityform_labels_activate()
    {
        global $wpdb;
        $collate = '';
        if ($wpdb->has_cap('collation')) {
            if (! empty($wpdb->charset)) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if (! empty($wpdb->collate)) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gfform_labels (
              id int(7) NOT NULL auto_increment,
              gfform_id varchar(255) default NULL,
              gflabel_name varchar(255) default NULL,
              PRIMARY KEY  (id))" . $collate
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gf_gfolders (
              id int(7) NOT NULL auto_increment,
              gfform_id varchar(255) default NULL,
              gf_gfolder varchar(255) default NULL,
              gfform_name varchar(255) default NULL,
              PRIMARY KEY  (id))" . $collate
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gf_label_tags (
              id int(11) NOT NULL auto_increment,
              gf_gfolder varchar(255) default NULL,
              PRIMARY KEY  (id))" . $collate
        );
    }
}


/**
 * flgf_gravityforms_labels_deactivate()
 *
 * Plugin Deactivation.
 */
if (! function_exists('flgf_gravityforms_labels_deactivate')) {
    function flgf_gravityforms_labels_deactivate()
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gfform_labels,{$wpdb->prefix}gf_gfolders,{$wpdb->prefix}gf_label_tags");
    }
}


add_filter('gform_addon_navigation', 'flgf_gform_set_menu');
function flgf_gform_set_menu($menus)
{
    $menus[] = array(
        'name'     => 'flgf_gform_folders',
        'label'    => __('Manage Folders', 'flgf'),
        'callback' => 'flgf_gform_folders',
    );
    $menus[] = array(
        'name'     => 'flgf_gform_labels',
        'label'    => __('Manage Labels', 'flgf'),
        'callback' => 'flgf_gform_labels',
    );
    return $menus;
}

function flgf_gform_folders()
{
    if (! current_user_can('install_plugins')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flgf'));
    }
    include plugin_dir_path(__FILE__) . ('gffolder_settings.php');
}

function flgf_gform_labels()
{
    if (! current_user_can('install_plugins')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flgf'));
    }
    include plugin_dir_path(__FILE__) . ('gflabel_settings.php');
}


function flgf_gform_new_label()
{
    if (! current_user_can('install_plugins')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flgf'));
    }
    include plugin_dir_path(__FILE__) . ('add_gflabel_settings.php');
}


function flgf_gfolders_plugin_setting($links)
{
    $settings_link = '<a href="' . esc_attr('admin.php?page=flgf_gform_labels') . '">' . esc_html__('Settings', 'flgf') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}


// Delete Folder
add_action('wp_ajax_flgf_delgffolder', 'flgf_delgffolder');
add_action('wp_ajax_nopriv_flgf_delgffolder', 'flgf_delgffolder');

if (! function_exists('flgf_delgffolder')) {
    function flgf_delgffolder()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        if (isset($_POST['folder_id'])) {
            if ($wpdb->delete($wpdb->prefix . 'gfform_labels', array( 'id' => sanitize_text_field($_POST['folder_id']) ))) {
                esc_html_e('Done');
            } else {
                esc_html_e('Error');
            }
        }
    }
}

// Delete Label
add_action('wp_ajax_flgf_delgflabel', 'flgf_delgflabel');
add_action('wp_ajax_nopriv_flgf_delgflabel', 'flgf_delgflabel');

if (! function_exists('flgf_delgflabel')) {
    function flgf_delgflabel()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        if (isset($_POST['label_id'])) {

            $items = $wpdb->get_row($wpdb->prepare("SELECT gf_gfolder FROM {$wpdb->prefix}gf_label_tags WHERE id= '%s'", sanitize_text_field($_POST['label_id'])));
            $label_name = $items->gf_gfolder;
            $wpdb->delete($wpdb->prefix . 'gf_gfolders', array( 'gf_gfolder' => sanitize_text_field($label_name) ));
            
            /*
            $results = $wpdb->get_results("SELECT gf_gfolder FROM {$wpdb->prefix}gf_gfolders WHERE gf_gfolder LIKE '%$label_name%'");
            $updated_label = "";
            $tot = count($results);
            foreach($results as $res){
                    $label = $res->gf_gfolder;
                    $updated_label = str_replace($label_name, "", $label);
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gf_gfolders SET gf_gfolder = '$updated_label' WHERE gf_gfolder LIKE '%$label_name%'"));
            }
            */
            
            if ($wpdb->delete($wpdb->prefix . 'gf_label_tags', array( 'id' => sanitize_text_field($_POST['label_id']) ))) {
                esc_html_e('true');
            } else {
                esc_html_e('Error');
            }
            
        }
    }
}

add_action('wp_ajax_flgf_updgffolder', 'flgf_updgffolder');
add_action('wp_ajax_nopriv_flgf_updgffolder', 'flgf_updgffolder');

if (! function_exists('flgf_updgffolder')) {
    function flgf_updgffolder()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        if (isset($_POST['folder_id'])) {
            $items = $wpdb->get_row($wpdb->prepare("SELECT  id FROM {$wpdb->prefix}gfform_labels WHERE gflabel_name= '%s'", sanitize_text_field($_POST['folder_name'])));
            if ($items->id != '') {
                esc_html_e('Error');
            } else {
                $OK = $wpdb->update("{$wpdb->prefix}gfform_labels", array( 'gflabel_name' => sanitize_text_field($_POST['folder_name']) ), array( 'id' => sanitize_text_field($_POST['folder_id']) ));
                esc_html_e('Done');
            }
        }
    }
}

add_action('wp_ajax_flgf_updatetags', 'flgf_updatetags');
add_action('wp_ajax_nopriv_flgf_updatetags', 'flgf_updatetags');

if (! function_exists('flgf_updatetags')) {
    function flgf_updatetags()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        if (isset($_POST['form_id'])) {
            if ($wpdb->update("{$wpdb->prefix}gf_gfolders", array( 'gf_gfolder' => sanitize_text_field($_POST['tagslist']) ), array( 'gfform_id' => sanitize_text_field($_POST['form_id']) ))) {
                esc_html_e('Done');
            } else {
                esc_html_e('Error');
            }
        }
    }
}


add_action('wp_ajax_flgf_addgftags', 'flgf_addgftags');
add_action('wp_ajax_nopriv_flgf_addgftags', 'flgf_addgftags');

if (! function_exists('flgf_addgftags')) {
    function flgf_addgftags()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        if (isset($_POST['form_id'])) {
            $wpdb->delete($wpdb->prefix . 'gf_gfolders', array( 'gfform_id' => sanitize_text_field($_POST['form_id']) ));
            $labels = sanitize_text_field($_POST['folder_name']);
            $labels_arr = explode(",",$labels);
            foreach($labels_arr as $label){
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO $wpdb->prefix" . "gf_gfolders (gfform_id, gf_gfolder, gfform_name) VALUES ('%d', '%s','%s')",
                        sanitize_text_field($_POST['form_id']),
                        sanitize_text_field($label),
                        sanitize_text_field($_POST['gfFormName'])
                    )
                );
            }
            
            /*
            $items = $wpdb->get_row($wpdb->prepare("SELECT  gfform_id FROM $wpdb->prefix" . "gf_gfolders WHERE gfform_id= '%s'", sanitize_text_field($_POST['label_id'])));
            
            if ($items->gfform_id > 0) {
                $OK = $wpdb->update(
                    $wpdb->prefix . 'gf_gfolders',
                    array(
                        'gfform_name' => sanitize_text_field($_POST['gfFormName']),
                        'gf_gfolder'  => sanitize_text_field($_POST['folder_name']),
                    ),
                    array( 'gfform_id' => sanitize_text_field($_POST['label_id']) )
                );
            } else {
                $OK = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO $wpdb->prefix" . "gf_gfolders (gfform_id, gf_gfolder, gfform_name) VALUES ('%d', '%s','%s')",
                        sanitize_text_field($_POST['label_id']),
                        sanitize_text_field($_POST['folder_name']),
                        sanitize_text_field($_POST['gfFormName'])
                    )
                );
            }
            */
            if (sanitize_text_field($_POST['form_id'])) {
                esc_html_e('true');
            } else {
                esc_html_e('Error');
            }
        }
    }
}


add_action('wp_ajax_flgf_updgflabel', 'flgf_updgflabel');
add_action('wp_ajax_nopriv_flgf_updgflabel', 'flgf_updgflabel');

if (! function_exists('flgf_updgflabel')) {
    function flgf_updgflabel()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        if (isset($_POST['label_id'])) {
            $items = $wpdb->get_row($wpdb->prepare("SELECT  id FROM {$wpdb->prefix}gf_label_tags WHERE gf_gfolder= '%s'", sanitize_text_field($_POST['label_name'])));
            if ($items->id != '') {
                esc_html_e('Error');
            } else {
                $OK = $wpdb->update("{$wpdb->prefix}gf_label_tags", array( 'gf_gfolder' => sanitize_text_field($_POST['label_name']) ), array( 'id' => sanitize_text_field($_POST['label_id']) ));
                esc_html_e('Done');
            }
        }
    }
}


add_action('wp_ajax_flgf_getallglabels', 'flgf_getallglabels');
add_action('wp_ajax_nopriv_flgf_getallglabels', 'flgf_getallglabels');

if (! function_exists('flgf_getallglabels')) {
    function flgf_getallglabels()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        $items    = $wpdb->get_results("SELECT gf_gfolder FROM {$wpdb->prefix}gf_label_tags ORDER BY gf_gfolder asc");
        $all_rows = array();
        foreach ($items as $item) {
            $all_rows[] = esc_html($item->gf_gfolder);
        }
        esc_html($all_rows);
    }
}


add_action('wp_ajax_flgf_gfformslists', 'flgf_gfformslists');
add_action('wp_ajax_nopriv_flgf_gfformslists', 'flgf_gfformslists');

if (! function_exists('flgf_gfformslists')) {
    function flgf_gfformslists()
    {
        if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
            wp_send_json_error('Invalid security token sent.');
            wp_die();
        }
        global $wpdb;
        $form_id = 0;
        $items   = $wpdb->get_results($wpdb->prepare("SELECT  title FROM {$wpdb->prefix}gf_form WHERE is_trash = 0 ORDER BY title asc"));
        $html    = '<table><thead><tr><th>Forms</th></tr></thead>';
        foreach ($items as $item) {
            $form_name = $item->title;
            if ($form_name != '') {
                $form_id = RGFormsModel::get_form_id($form_name);
            } else {
                $form_name = 'No form exists in the selected Folder!';
            }
            $html .= "<tr><td> $form_name <div class='row-actions'></span><span><a href='" . esc_attr(admin_url('admin.php?page=gf_edit_forms&id=' . $form_id)) . "' target='_blank'>Edit</a> | <a href='" . esc_attr('/?gf_page=preview&id=') . $form_id . "' target='_blank'>Preview</a> 
            <a href='" . esc_attr(admin_url('admin.php?page=gf_entries&id=' . $form_id)) . "' target='_blank'>Entries</a></span></div> </td>
            </tr>";
        }
        $html .= '</table>';
        echo wp_kses($html, array( 'a' => array( 'href' => true, 'title' => true, ), 'abbr' => array( 'title' => true, ), 'acronym' => array( 'title' => true, ), 'b' => array(), 'blockquote' => array( 'cite' => true, ), 'cite' => array(), 'code' => array(), 'del' => array( 'datetime' => true, ), 'em' => array(), 'i' => array(), 'q' => array( 'cite' => true, ), 'strike' => array(), 'table' => array(), 'thead' => array(), 'div' => array('class'=> true), 'td' => array(), 'th' => array(), 'tr' => array(), 'span' => array(), ));
    }
}

function flgf_get_labels()
{
    global $wpdb;
    $items = $wpdb->get_results("SELECT gf_gfolder FROM {$wpdb->prefix}gf_label_tags");
    $data  = array();
    foreach ($items as $item) {
        $data[] = array(
            'id'   => $item->gf_gfolder,
            'text' => $item->gf_gfolder,
        );
    }
    return $data;
}



add_action('wp_ajax_flgf_getformsbyid', 'flgf_getformsbyid');
add_action('wp_ajax_nopriv_flgf_getformsbyid', 'flgf_getformsbyid');


function flgf_getformsbyid()
{
    if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
        wp_send_json_error('Invalid security token sent.');
        wp_die();
    }

    global $wpdb;
    $label_name = esc_sql($wpdb->esc_like(sanitize_text_field($_POST['genre'])));

    if (isset($_POST['genre'])) {
        $label_name = esc_sql($wpdb->esc_like(sanitize_text_field($_POST['genre'])));
    }
    $form_id = 0;
    $items   = $wpdb->get_results("SELECT gfform_id as gformids FROM {$wpdb->prefix}gfform_labels WHERE gflabel_name = '$label_name' AND gfform_id !=''");
    $html    = '<table><thead><tr><th>Forms</th></tr></thead>';
    foreach ($items as $item) {
        $form_name = $item->gformids;
        if ($form_name != '') {
            $form_name;
            $form_id = RGFormsModel::get_form_id($form_name);
        } else {
            $form_name = 'No form exists in the selected Folder!';
        }
        $gform_edit_link    = admin_url('admin.php?page=gf_edit_forms&id=' . $form_id);
        $gform_preview_link = '/?gf_page=preview&id=' . $form_id;
        $gform_entries_link = admin_url('admin.php?page=gf_entries&id=' . $form_id);

        $html .= "<tr>
        <td> $form_name <div class='row-actions'></span><span><a href='$gform_edit_link' target='_blank'>Edit</a> | <a href='$gform_preview_link' target='_blank'>Preview</a> | <a href='$gform_entries_link' target='_blank'>Entries</a></span></div> </td>
    </tr>";
    }
    if (! $items) {
        $html .= '<tr><td>No form exists in the selected Folder!</td></tr>';
    }
    $html .= '</table>';
    echo wp_kses($html, array( 'a' => array( 'href' => true, 'title' => true, ), 'abbr' => array( 'title' => true, ), 'acronym' => array( 'title' => true, ), 'b' => array(), 'blockquote' => array( 'cite' => true, ), 'cite' => array(), 'code' => array(), 'del' => array( 'datetime' => true, ), 'em' => array(), 'i' => array(), 'q' => array( 'cite' => true, ), 'strike' => array(), 'table' => array(), 'thead' => array(), 'div' => array('class'=> true), 'td' => array(), 'th' => array(), 'tr' => array(), 'span' => array(), ));
}





add_action('wp_ajax_flgf_getformsbylabelid', 'flgf_getformsbylabelid');
add_action('wp_ajax_nopriv_flgf_getformsbylabelid', 'flgf_getformsbylabelid');


function flgf_getformsbylabelid()
{
    if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
        wp_send_json_error('Invalid security token sent.');
        wp_die();
    }
    global $wpdb;
    $label_name = esc_sql($wpdb->esc_like(sanitize_text_field($_POST['label'])));
    if (isset($_POST['label'])) {
        $label_name = esc_sql($wpdb->esc_like(sanitize_text_field($_POST['label'])));
    }

    $items = $wpdb->get_results("SELECT {$wpdb->prefix}gf_gfolders.gfform_id as gformids, {$wpdb->prefix}gf_gfolders.gfform_name FROM {$wpdb->prefix}gf_gfolders WHERE {$wpdb->prefix}gf_gfolders.gf_gfolder LIKE '%$label_name%' AND {$wpdb->prefix}gf_gfolders.gfform_id !=''");

    $html = '<table><thead><tr><th>Forms</th></tr></thead>';
    foreach ($items as $item) {
        $form_name = $item->gformids;
        if ($form_name != '') {
            $form_name;
            $form_id = RGFormsModel::get_form_id($form_name);
        } else {
            $form_name = 'No form exists in the selected Label!';
        }
        $gform_edit_link    = admin_url('admin.php?page=gf_edit_forms&id=' . $form_id);
        $gform_preview_link = '/?gf_page=preview&id=' . $form_id;
        $gform_entries_link = admin_url('admin.php?page=gf_entries&id=' . $form_id);

        $html .= "<tr>
        <td> $item->gfform_name <div class='row-actions'></span><span><a href='$gform_edit_link' target='_blank'>Edit</a> | <a href='$gform_preview_link' target='_blank'>Preview</a> | <a href='$gform_entries_link' target='_blank'>Entries</a></span></div> </td>
    </tr>";
    }
    if (! $items) {
        $html .= '<tr><td>No form exists in the selected Label!</td></tr>';
    }

    $html .= '</table>';
    echo wp_kses($html, array( 'a' => array( 'href' => true, 'title' => true, ), 'abbr' => array( 'title' => true, ), 'acronym' => array( 'title' => true, ), 'b' => array(), 'blockquote' => array( 'cite' => true, ), 'cite' => array(), 'code' => array(), 'del' => array( 'datetime' => true, ), 'em' => array(), 'i' => array(), 'q' => array( 'cite' => true, ), 'strike' => array(), 'table' => array(), 'thead' => array(), 'div' => array('class'=> true), 'td' => array(), 'th' => array(), 'tr' => array(), 'span' => array(), ));
}




add_action('wp_ajax_flgfpostgffolder', 'flgfpostgffolder');
add_action('wp_ajax_nopriv_flgfpostgffolder', 'flgfpostgffolder');

function flgfpostgffolder()
{
    if (! check_ajax_referer('flgf-security-nonce', 'security', false)) {
        wp_send_json_error('Invalid security token sent.');
        wp_die();
    }

    global $wpdb;

    if (isset($_POST['folder'])) {
        $folder_name = sanitize_text_field($_POST['folder']);
        $form_name   = sanitize_text_field($_POST['form']);

        $form_check = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gfform_labels WHERE gfform_id = '$form_name'");

        $count     = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gfform_labels WHERE gfform_id = '$form_name'");
        $all_check = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gfform_labels WHERE gfform_id = '$form_name' AND gflabel_name='$folder_name'");

        if ($all_check > 0) {
            $false_msg = '' . $form_name . ' is already assigned to ' . $folder_name . '.';
        } else {
            if ($count > 0) {
                // $false_msg = "This is already Exist!";
                $rec_update = $wpdb->update(
                    "{$wpdb->prefix}gfform_labels",
                    array(
                        'gflabel_name' => $folder_name,
                    ),
                    array(
                        'gfform_id' => $form_name,
                    )
                );

                if ($rec_update) {
                    $false_msg = '' . $form_name . ' is assigned to ' . $folder_name . '.';
                }
            } else {
                $items = $wpdb->query("INSERT INTO {$wpdb->prefix}gfform_labels (gfform_id, gflabel_name) VALUES ('$form_name', '$folder_name')");
            }
        }
        if ($items) {
            $msg = '' . $form_name . ' is assigned to ' . $folder_name . '.';
            esc_html_e($msg);
        } else {
            esc_html_e($false_msg);
        }
    }
}

add_action('admin_footer', 'flgf_footer_scripts');
function flgf_footer_scripts()
{
    $bj_labels = flgf_get_labels();
    ?>
    <script>
        jQuery(function ($) {
            const dataaa = <?php echo json_encode($bj_labels); ?>;
            $(function () {
                const select = $('.flgf_labels_list');
                select.select2({
                    placeholder: 'Select an option',
                    data: dataaa
                })
                    .on('change', (event) => {
                        const selecions = select.select2('data')
                            .map((element) => parseInt(element.id, 10));

                    });
            });
        });

        jQuery(document).ready(function ($) {
            aajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            jQuery('.folderlist').on('change', function () {
                var folder_title = $(this).val();
                var form_title = $(this).next().val();
                $(this).removeAttr("selected");
                $.ajax({
                    url: aajaxurl,
                    data: {action: "flgfpostgffolder",security: "<?php echo wp_create_nonce('flgf-security-nonce'); ?>", folder: folder_title, form: form_title},
                    type: 'post',
                    success: function (response) {
                        response = response.slice(0, -1);
                        $('html, body').animate({
                            scrollTop: $("#message").offset().top - 200
                        }, 500);
                        $("#message p").text(response);
                        $("#message").show("slow").delay(3000).hide("slow");
                    }

                });
                jQuery("<div id='message' class='updated notice is-dismissible'><p></p></div>").insertAfter(".gf_browser_chrome h2");
                jQuery("#message").remove();

            });


            $(".add-gftags").click(function () {

                var formId = $(this).data("labelbtnid");
                var comb_tags = "";

                var inputID = 'input[name="hidden-gftags-' + formId + '"]';
                var labelsId = '#flgf-label-' + formId;
                var gfFormNameID = '#gf-name-' + formId;
                var gfLabelsList = '#gf-tagslist-' + formId;

                var newVal = $('body').find(labelsId).val();
                var gfFormName = $('body').find(gfFormNameID).val();
                var prev_tags = $('body').find(gfLabelsList).val();
                var comb_tags = newVal.toString();

                $.ajax({
                    type: 'post',
                    url: aajaxurl,
                    data: {
                        action: "flgf_addgftags",
                        form_id: formId,
                        folder_name: comb_tags,
                        gfFormName: gfFormName,
                        security: "<?php echo wp_create_nonce('flgf-security-nonce'); ?>",
                    },
                    success: function (response) {
                        if (response != "Error0") {
                            $('html, body').animate({
                                scrollTop: $("#message").offset().top - 200
                            }, 500);
                            let msg = "Label(s) have been assigned";
                            $("#message p").text(msg);
                            $("#message").show("slow").delay(3000).hide("slow");
                        } else {
                            console.log(JSON.stringify(error));
                        }
                    }

                });
                jQuery("<div id='message' class='updated notice is-dismissible'><p></p></div>").insertAfter(".gf_browser_chrome h2");
                jQuery("#message").remove();
            });


            function removeValue(list, value) {
                list = list.split(',');
                list.splice(list.indexOf(value), 1);
                return list.join(',');
            }


            //Delete Tags from database
            $(".remove-gftag").click(function () {

                var form_id = $(this).data("removegfid");
                var tagVal = $(this).data("value");
                var dataid = $(this).data("id");
                var inputID = '#tag-' + form_id;
                var taglistID = '#gf-tagslist-' + dataid;
                var listfield = $('body').find(taglistID);
                var newVal = $('body').find(inputID).hide();
                var taglistVal = $('body').find(taglistID).val();


                var finalResult = removeValue(taglistVal, tagVal);
                $('body').find(taglistID).val(finalResult);
                var getUpdatedTag = $('body').find(taglistID).val();


                $.ajax({
                    type: 'post',
                    url: aajaxurl,
                    data: {action: "flgf_updatetags", security: "<?php echo wp_create_nonce('flgf-security-nonce'); ?>", tagslist: getUpdatedTag, form_id: dataid},
                    success: function (response) {
                        if (response == "Done0") {
                        } else {
                            console.log(JSON.stringify(error));
                        }
                    }

                });
            });


        });

    </script>
    <?php
}






add_action('init', 'flgf_gfolder_admin_script');
function flgf_gfolder_admin_script()
{
    global $css_arr;

    wp_register_style('gflabel-admin', plugins_url('css/admin-style.css', dirname(__FILE__)));
    wp_enqueue_style('gflabel-admin');

    wp_register_style('gflabel-select2', plugins_url('css/select2.min.css', dirname(__FILE__)));
    wp_enqueue_style('gflabel-select2');

    wp_register_script('gflabel-scripts', plugins_url('js/admin-scripts.js?t=' . time(), dirname(__FILE__)), array( 'jquery' ));
    wp_register_script('gflabel-scripts-select2', plugins_url('js/select2.min.js?t=' . time(), dirname(__FILE__)), array( 'jquery' ));

    wp_localize_script('gflabel-scripts', 'gfDel', array( 'gfdelajaxurl' => admin_url('admin-ajax.php'),'security'=> wp_create_nonce('flgf-security-nonce') ));

    wp_localize_script(
        'gflabel-scripts',
        'flgfpostFolder',
        array(
            'flgfpostfolderajaxurl' => admin_url('admin-ajax.php'),
            'security'              => wp_create_nonce('flgf-security-nonce'),
        )
    );

    wp_localize_script(
        'gflabel-scripts',
        'updategffolder',
        array(
            'gfupdateajaxurl' => admin_url('admin-ajax.php'),
            'security'        => wp_create_nonce('flgf-security-nonce'),
        )
    );

    wp_localize_script(
        'gflabel-scripts',
        'getAllgforms',
        array(
            'getAllgformsajaxurl' => admin_url('admin-ajax.php'),
            'security'            => wp_create_nonce('flgf-security-nonce'),
        )
    );

    wp_localize_script(
        'gflabel-scripts',
        'getgformsbyid',
        array(
            'getgformsbyidajaxurl' => admin_url('admin-ajax.php'),
            'security'             => wp_create_nonce('flgf-security-nonce'),
        )
    );

    wp_enqueue_script('gflabel-scripts');
    wp_enqueue_script('gflabel-scripts-select2');
}

add_action('wp_enqueue_scripts', 'flgf_gfolder_admin_script');

add_action('admin_enqueue_scripts', 'flgf_gfolder_admin_script');
add_action('gform_enqueue_scripts', 'flgf_gfolder_admin_script', 10, 2);



add_action('gform_form_actions', 'flgf_gf_folder_link', 10, 4);
function flgf_gf_folder_link($actions, $form_id)
{
    global $wpdb, $table_prefix;
    $gform_table        = $wpdb->prefix . 'gf_form';
    $gffolder_table     = $wpdb->prefix . 'gfform_labels';
    $gffoldertags_table = $wpdb->prefix . 'gf_gfolders';
    $gflabel_tags_table = $wpdb->prefix . 'gf_label_tags';
    $tagarr             = '';
    $getFolder          = '';
    $getFolderTags      = '';
    $gformFolders       = $wpdb->get_results('SELECT DISTINCT gflabel_name  FROM ' . $gffolder_table . ' ORDER BY gflabel_name ASC');
    $forminfo           = RGFormsModel::get_form($form_id);
    $form_title         = $forminfo->title;
    $get_all_labels     = $wpdb->get_results('SELECT gf_gfolder FROM ' . $gflabel_tags_table . '');
    $post               = $wpdb->get_row('SELECT * FROM ' . $gffolder_table . " WHERE gfform_id = '$form_title'");
    $gformFoldersTags   = $wpdb->get_results('SELECT * FROM ' . $gffoldertags_table . " WHERE gfform_id = '$form_id'");
    
    $comma_string = array();
    foreach($gformFoldersTags as $gfTags){
        $comma_string[] = $gfTags->gf_gfolder;
    }
    $getFolderTags = implode(",", $comma_string);
    
    if (isset($post->gflabel_name)) {
        $getFolder = $post->gflabel_name;
    }
    /*
    if (isset($gformFoldersTags->gf_gfolder)) {
        $getFolderTags = $gformFoldersTags->gf_gfolder;
    }
    */
    if ($getFolder == '') {
        $foler_name = 'Folder';
    } else {
        $foler_name = $getFolder . '-selected';
    }

    $abc = "<select name='gf_label_name1' class='folderlist form-control'><option selected='selected' disabled>$foler_name</option>";
    foreach ($gformFolders as $gformFolder) {
        if ($gformFolder->gflabel_name) {
            $abc .= "<option value='$gformFolder->gflabel_name'>" . esc_html($gformFolder->gflabel_name) . '</option>';
        }
    }

    $getFolderTags_arr = preg_split('/\,/', $getFolderTags);
    foreach ($get_all_labels as $label) {
        $isSelected = (in_array($label->gf_gfolder, $getFolderTags_arr)) ? ' selected="selected"' : '';
        $tagarr    .= '<option value="' . $label->gf_gfolder . '" "' . $isSelected . '">' . $label->gf_gfolder . '</option>';
    }
    // Tags Array
    $abc                      .= "</select><input type='hidden' id='gform_name' class='gformID' value='$form_title'>";
    $abc                      .= "<div class='gftag-section'><select id='flgf-label-$form_id' class='flgf_labels_list' multiple>$tagarr</select><a href='javascript:void(0)' class='add-gftags' data-labelbtnid='$form_id'>Save Tags</a><input type='hidden' name='gf-name' id='gf-name-$form_id' value='$form_title'><input type='hidden' name='gf-tagslist' id='gf-tagslist-$form_id' value='$getFolderTags'></div>";
    $actions['gfolder_action'] = $abc;

    return $actions;
}
