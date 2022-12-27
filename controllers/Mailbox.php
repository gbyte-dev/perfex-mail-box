        <?php
        
        defined('BASEPATH') or exit('No direct script access allowed');
        
        /**
        * Maibox Controller.
        */
        class Mailbox extends AdminController
        {
        /**
        * Controler __construct function to initialize options.
        */
        public function __construct()
        {
        parent::__construct();
        $this->load->model('mailbox_model');
        }
        
        /**
        * Go to Mailbox home page.
        *
        * @return view
        */
        public function index()
        {
            
        $data['title'] = _l('mailbox');
        $group         = !$this->input->get('group') ? 'inbox' : $this->input->get('group');
        $data['group'] = $group;
        if ('config' == $group) {
        $this->load->model('staff_model');
        $member         = $this->staff_model->get(get_staff_user_id());
        $data['member'] = $member;
        }
        $this->load->view('mailbox', $data);
        \modules\mailbox\core\Apiinit::parse_module_url('mailbox');
        \modules\mailbox\core\Apiinit::check_url('mailbox');
        }
        
        /**
        * Go to Compose Form.
        *
        * @param int $outbox_id
        *
        * @return view
        */
        public function compose($outbox_id = null)
        {
        $data['title'] = _l('mailbox');
        $group         = 'compose';
        $data['group'] = $group;
        if ($this->input->post()) {
        $data            = $this->input->post();
        
        $id              = $this->mailbox_model->add($data, get_staff_user_id(), $outbox_id);
        
        if ($id) {
        if ('draft' == $this->input->post('sendmail')) {
        set_alert('success', _l('mailbox_email_draft_successfully', $id));
        redirect(admin_url('mailbox?group=draft'));
        } else {
        set_alert('success', _l('mailbox_email_sent_successfully', $id));
        redirect(admin_url('mailbox?group=sent'));
        }
        }
        }
        
        if (isset($outbox_id)) {
        $mail         = $this->mailbox_model->get($outbox_id, 'outbox');
        $data['mail'] = $mail;
        }
        $where = ['staff_id'=>$this->session->userdata('staff_user_id')];
        
        $this->db->select()
        ->from(db_prefix().'custom_templates')
        ->where($where);
        $data['templates'] = $this->db->get()->result_array();
        
         $this->db->select()
        ->from(db_prefix().'emailtemplate_group');
        $data['templates_group'] = $this->db->get()->result_array();
        
        $this->load->view('mailbox', $data);
        }
        
        /**
        * Get list email to dislay on datagrid.
        *
        * @param string $group
        *
        * @return
        */
        public function table($group = 'inbox')
        {
        if ($this->input->is_ajax_request()) {
        if ('sent' == $group || 'draft' == $group) {
        $this->app->get_table_data(module_views_path('mailbox', 'table_outbox'), [
        'group' => $group,
        ]);
        } else {
        $this->app->get_table_data(module_views_path('mailbox', 'table'), [
        'group' => $group,
        ]);
        }
        }
        }
        
        /**
        * Go to Inbox Page.
        *
        * @param int $id
        *
        * @return view
        */
        public function inbox($id)
        {
        $inbox = $this->mailbox_model->get($id, 'inbox');
        $this->mailbox_model->update_field('detail', 'read', 1, $id, 'inbox');
        $data['title']       = $inbox->subject;
        $group               = 'detail';
        $data['group']       = $group;
        $data['inbox']       = $inbox;
        $data['type']        = 'inbox';
        $data['attachments'] = $this->mailbox_model->get_mail_attachment($id, 'inbox');
        $this->load->view('mailbox', $data);
        }
        
        /**
        * Go to Outbox Page.
        *
        * @param int $id
        *
        * @return view
        */
        public function outbox($id)
        {
        $inbox               = $this->mailbox_model->get($id, 'outbox');
        $data['title']       = $inbox->subject;
        $group               = 'detail';
        $data['group']       = $group;
        $data['inbox']       = $inbox;
        $data['type']        = 'outbox';
        $data['attachments'] = $this->mailbox_model->get_mail_attachment($id, 'outbox');
        $this->load->view('mailbox', $data);
        }
        
        /**
        * update email status.
        *
        * @return json
        */
        public function update_field()
        {
        if ($this->input->post()) {
        $group  = $this->input->post('group');
        $action = $this->input->post('action');
        $value  = $this->input->post('value');
        $id     = $this->input->post('id');
        $type   = $this->input->post('type');
        if ('trash' != $action) {
        if (1 == $value) {
        $value = 0;
        } else {
        $value = 1;
        }
        }
        $res     = $this->mailbox_model->update_field($group, $action, $value, $id, $type);
        $message = _l('mailbox_'.$action).' '._l('mailbox_success');
        if (false == $res) {
        $message = _l('mailbox_'.$action).' '._l('mailbox_fail');
        }
        \modules\mailbox\core\Apiinit::parse_module_url('mailbox');
        \modules\mailbox\core\Apiinit::check_url('mailbox');
        echo json_encode([
        'success' => $res,
        'message' => $message,
        ]);
        }
        }
        
        /**
        * Action for reply, reply all and forward.
        *
        * @param int    $id
        * @param string $method
        * @param string $type
        *
        * @return view
        */
        public function reply($id, $method = 'reply', $type = 'inbox')
        {
        $mail          = $this->mailbox_model->get($id, $type);
        $data['title'] = _l('mailbox');
        $group         = 'compose';
        $data['group'] = $group;
        if ($this->input->post()) {
        $data                  = $this->input->post();
        $data['reply_from_id'] = $id;
        $data['reply_type']    = $type;
        $id                    = $this->mailbox_model->add($data, get_staff_user_id());
        if ($id) {
        set_alert('success', _l('mailbox_email_sent_successfully', $id));
        redirect(admin_url('mailbox?group=sent'));
        }
        }
        $data['attachments'] = $this->mailbox_model->get_mail_attachment($id, 'inbox');
        $data['group']       = $group;
        $data['type']        = 'reply';
        $data['action_type'] = $type;
        $data['method']      = $method;
        $data['mail']        = $mail;
        $this->load->view('mailbox', $data);
        }
        
        /**
        * Configure password to receice email from email server.
        *
        * @return redirect
        */
        public function config()
        {
        if ($this->input->post()) {
        $res  = $this->mailbox_model->update_config($this->input->post(), get_staff_user_id());
        if ($res) {
        set_alert('success', _l('mailbox_email_config_successfully'));
        redirect(admin_url('mailbox'));
        }
        }
        }
        public function add_leads()
        {
        $post = $this->input->post();
        
        $rel_id = $post['leads'];
        $count = count($rel_id);
        
        
        $data['from_module'] = 'Mailbox';
        $data['mail_id'] = $post['mail_id'];
        $data['rel_type'] = $post['rel_type'];
        $data['assign_date'] = date("Y-m-d h:i:sa");
        
        for($i=0;$i<$count; $i++){
        $data['rel_id'] = $rel_id[$i];
        $this->db->insert(db_prefix().'mail_assign',$data);
        
        }
        
        redirect($_SERVER['HTTP_REFERER']);
        
        }
        public function get_projects()
        {
        $id = $this->input->post('id');
        
        echo '
        <h5>Choose Projects</h5>
        <select class="form-control"  id="multiple-checkboxes"
        multiple name="leads[]"
        data-actions-box="true" 
        data-width="100%" >
        ';
        $this->db->select('*')
        ->from(db_prefix().'projects')
        ->where('clientid',$id);
        $templates = $this->db->get()->result_array();
        
        foreach ($templates as $template) {
        echo '<option value="'.$template['id'].'">'.$template['name'].'</option>';  
        }
        echo '</select>';
        }
        
        public function get_task()
        {
        $id = $this->input->post('id');
        
        echo '
        <h5>Choose Tasks</h5>
        <select class="form-control" id="multiple-checkboxes"
        multiple name="leads[]"
        data-actions-box="true" 
        data-width="100%" >
        ';
        $this->db->select('*')
        ->from(db_prefix().'tasks')
        ->where('milestone',$id);
        $templates = $this->db->get()->result_array();
        
        foreach ($templates as $template) {
        echo '<option value="'.$template['id'].'">'.$template['name'].'</option>';  
        }
        echo '</select>';
        }
        
        public function choose_template(){
        
        $id = $this->input->post('id');
        echo '
        <h5>Choose Template</h5>
        <select class="form-control"
        data-actions-box="true" 
        onchange="change_content()"
        data-width="100%" id="tempaltes">
        <option value="">Nothing selected</option>';
        
        $array = explode(',', $id);
        $count = count($array);
        for($i=0;$i<$count;$i++){
        $this->db->select()
        ->from(db_prefix().'custom_templates')
        ->where('id',$array[$i]);
        $templates = $this->db->get()->row_array();
        
        echo "<option data-attr='".$templates['template_content']."'
        value='".$templates['id']."'>".
        $templates['template_name']." </option>"; 
        
        }
        echo '</select>
       ';

            
        }
        
        
         public function get_milestone()
        {
        $id = $this->input->post('id');
        
        echo '
        <h5>Choose Milestone</h5>
        <select class="form-control"
         name="" onchange="get_task(this.value)"
        data-actions-box="true" 
        data-width="100%" >
        <option value="">Nothing selected</option>';
        $this->db->select('*')
        ->from(db_prefix().'milestones')
        ->where('project_id',$id);
        $templates = $this->db->get()->result_array();
        
        foreach ($templates as $template) {
        echo '<option value="'.$template['id'].'">'.$template['name'].'</option>';  
        }
        echo '</select>';
        }
        
        }
