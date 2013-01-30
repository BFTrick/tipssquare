<?php
/*
Plugin Name: Bainternet Posts Creation Limits
Plugin URI: http://en.bainternet.info/category/plugins
Description: this plugin helps you to limit the number of posts/pages/custom post types each user can create on your site.
Version: 2.9
Author: bainternet
Author URI: http://en.bainternet.info
*/
/*  Copyright 2012 bainternet  (email : admin@bainternet.info)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!class_exists('bapl')){
	class bapl{
		
		protected $_MU;

		public $current_message;
		
		//constructor
		public function __construct(){
			if (function_exists('is_multisite') && is_multisite())
				$this->_MU = true;
			else
				$this->_MU = false;
			//hook limits check function to admin head.
			//add menu hook
			add_action('admin_head',array($this,'bapl_limit_post_count'));
			add_action('admin_head',array($this,'remove_add_new'));
			add_action('admin_menu',array($this, 'bapl_menu'));
			//create options
			add_action('admin_init', array($this,'wpsnfl_init'));
			add_shortcode('IN_LIMIT',array($this,'limits_shortcode_handler'));
			add_shortcode('in_limit',array($this,'limits_shortcode_handler'));

			//limit xml-rpc
			add_filter('wp_insert_post_empty_content',array($this,'limit_xml_rpc'));
		}

		/**
		 * limit_xml_rpc limit xml-rpc user
		 * @since 2.5
		 * @param  boolean $maybe  
		 * @param  array $postarr
		 * @return true to limit false to allow
		 */
		public function limit_xml_rpc($maybe,$postarr = array()){
			//exit early if not xmlrpc request
			if (!defined('XMLRPC_REQUEST') ||  XMLRPC_REQUEST != true)
				return $maybe;
			
			if (isset($postarr['post_post_type']) &&  isset($postarr['post_author']) && $this->limitUser($postarr['post_author'],$postarr['post_type']))
				return apply_filters('bapl_xml_rpc_limit',true);

			return $maybe;

		}

		/**
		 * limitUser this is the money function which checks to limit a user by post count
		 * @since 2.5
		 * @param  int  $user_id 
		 * @param  strin  $type    post type
		 * @param  boolean $use_m   use shortcode message flag
		 * @return true to limit false to allow
		 */
		public function limitUser($user_id = null,$type = null,$use_m = true){

			//exit early if no settings
			$options = $this->bapl_getOptions('bapl');
			if (!isset($options['rules']))
				return false;

			if ($user_id == null){
				global $current_user;
				get_currentuserinfo();
				$user_id = $current_user->id;
				if ($user_id <= 0)
					return true;
			}

			if ($type == null){
				global $typenow;
				$type = isset($typenow)? $typenow: 'post';
			}

			if ($this->_MU) { 
				if ( current_user_can('manage_network') )
					return false;
			}elseif( current_user_can('manage_options') ){
				return false;
			}
			
			global $wpdb;
			//check old limits by role first
			$rules_BY_ANY = $this->get_sub_array($options['rules'],'role','ANY');
			if ($rules_BY_ANY){
				foreach((array)$rules_BY_ANY as $ke => $arr){
					if ($arr['post_type'] == $type || $arr['post_type'] == "any"){
						//$count_posts = get_posts(array('author'=>$user_id,'post_type' => $type,'post_status' => $arr['status'],'fields' => 'ids'));
						$ptype = ($arr['post_type'] == 'any')? "IN ('".implode("', '",get_post_types('', 'names')). "')" : " = '".$arr['post_type']."'";
						$time = (isset($arr['time_span']) && $arr['time_span'] != "FOREVER" ) ? " AND TIMEDIFF(NOW(), post_date) < '".$arr['time_span']."'" : "";
						$pstatus = ($arr['status'] == 'any') ? "IN ('publish', 'pending', 'draft', 'future', 'private', 'trash')": " = '".$arr['status']."'";
						$count = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status ". $pstatus ." AND post_author = $user_id AND post_type $ptype".$time);
						$count = apply_filters('bapl_Count_filter', $count,$arr, $user_id);
						if($count >= $arr['limit']){
							if ($use_m)
								$this->current_message = $arr['message'];
							return true;
						}
					}
				}
			}
				
			//check limit by role
			$current_role = $this->balp_get_current_user_role();
			$rules_BY_roles = $this->get_sub_array($options['rules'],'role',$current_role);
			if ($rules_BY_roles){
				foreach((array)$rules_BY_roles as $ke => $arr){
					if ($arr['post_type'] == $type || $arr['post_type'] == "any"){
						//$count_posts = get_posts(array('author'=>$user_id,'post_type' => $type,'post_status' => $arr['status'],'fields' => 'ids'));
						$ptype = ($arr['post_type'] == 'any')? "IN ('".implode("', '",get_post_types('', 'names')). "')" : " = '".$arr['post_type']."'";
						$time = (isset($arr['time_span']) && $arr['time_span'] != "FOREVER" ) ? " AND TIMEDIFF(NOW(), post_date) < '".$arr['time_span']."'" : "";
						$pstatus = ($arr['status'] == 'any') ? "IN ('publish', 'pending', 'draft', 'future', 'private', 'trash')": " = '".$arr['status']."'";
						$count = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status ". $pstatus ." AND post_author = $user_id AND post_type $ptype".$time);
						$count = apply_filters('bapl_Count_filter', $count,$arr, $user_id);
						if($count >= $arr['limit']){
							if ($use_m)
								$this->current_message = $arr['message'];
							return true;
						}
					}
				}
			}
				
			//check limit by user id
			$rules_BY_id = $this->get_sub_array($options['rules'],'role',$current_user->ID);
			if ($rules_BY_id){
				foreach((array)$rules_BY_id as $ke => $arr){
					if ($arr['post_type'] == $type || $arr['post_type'] == "any"){
						$ptype = ($arr['post_type'] == 'any')? "IN ('".implode("', '",get_post_types('', 'names')). "')" : " = '".$arr['post_type']."'";
						$time = (isset($arr['time_span']) && $arr['time_span'] != "FOREVER" ) ? " AND TIMEDIFF(NOW(), post_date) < '".$arr['time_span']."'" : "";
						$pstatus = ($arr['status'] == 'any') ? "IN ('publish', 'pending', 'draft', 'future', 'private', 'trash')": " = '".$arr['status']."'";
						$count = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status ". $pstatus ." AND post_author = $user_id AND post_type $ptype".$time);
						$count = apply_filters('bapl_Count_filter', $count,$arr, $user_id);
						if($count >= $arr['limit']){
							if ($use_m)
								$this->current_message = $arr['message'];
							return true;
						}
					}
				}
			}

			return false;
		}

		/**
		 * limits_shortcode_handler 
		 * @since 2.4
		 * @param  array $atts 
		 * @param  string $content 
		 * @return string
		 */
		public function limits_shortcode_handler($atts,$content = NULL){
			extract(shortcode_atts(array(
				'message' => __('You are not allowed to create any more'),
				'm' => __('You please login to post'),
				'use_m' => 'true',
				'type' =>'post'
			), $atts));
			if (!is_user_logged_in())
				return apply_filters('bapl_shortcode_not_logged_in',$m);

			global $current_user;
			get_currentuserinfo();
			if ($this->_MU) { 
				if ( current_user_can('manage_network') )
					return apply_filters('bapl_shortcode_network_admin',do_shortcode($content));
			}elseif( current_user_can('manage_options') ){
				return apply_filters('bapl_shortcode_admin',do_shortcode($content));
			}

			if ($this->limitUser($current_user->ID,$type)){
				if ($use_m == 'true')
					return apply_filters('bapl_shortcode_limited',$this->current_message);
				else
					return apply_filters('bapl_shortcode_limited',$message);
			}
			
			//all good return the content
			return apply_filters('bapl_shortcode_ok',do_shortcode($content));
		}
		
		public function remove_add_new(){
			global $pagenow ,$current_user,$typenow;
			if (is_admin() && $pagenow=='edit.php'){
				get_currentuserinfo();
				if ($this->limitUser($current_user->ID,$typenow)){
					$this->bapl_not_allowed_remove_links();
				}
			}
		}
		
		public function bapl_not_allowed_remove_links(){

			add_action('admin_footer',array($this,'hide_links'));
		}
		
		//remove links
		public function hide_links(){
			global $typenow;
			if ('post' == $typenow)
				$href='post-new.php';
			else
				$href='post-new.php?post_type='.$typenow;
			?>
			<script>
				jQuery(document).ready(function() {
					jQuery('.add-new-h2').remove();
					jQuery('[href$="<?php echo $href;?>"]').remove();
				});
			
			</script>
			<?php
		}
		
		//limit post type count per user 
		public function bapl_limit_post_count(){
			global $pagenow ,$current_user,$typenow;
			
			if (is_admin() && $pagenow=='post-new.php'){
				$options = $this->bapl_getOptions('bapl');
				if (!isset($options['rules']))
					return;

				get_currentuserinfo();
				if ($this->_MU) { 
					if ( current_user_can('manage_network') )
						return;
				}elseif( current_user_can('manage_options') ){
					return;
				}
				
				if ($this->limitUser($current_user->ID,$typenow)){
					$this->bapl_not_allowed($this->current_message);
					exit;
				}
				
				do_action('post_creation_limits_custom_checks',$typenow,$current_user->ID);
			}
		}

		//add menu function
		public function bapl_menu(){
			 
			if ($this->_MU) { // Add a new submenu under Settings:
				$hook = add_options_page(__('Posts Creation limits','Bainternet_WPSNFL'), __('Posts Creation limits','Bainternet_WPSNFL'), 'manage_network', __FILE__, array($this,'bapl_settings_page'));
			}else{
				$hook = add_options_page(__('Posts Creation limits','Bainternet_WPSNFL'), __('Posts Creation limits','Bainternet_WPSNFL'), 'manage_options', __FILE__, array($this,'bapl_settings_page'));
			}
			add_action('load-'.$hook,'add_thickbox');
		}
		
		//register options api
		public function wpsnfl_init(){
			register_setting( 'bapl_Options', 'bapl' );
			$this->bapl_getOptions();
		}
		
		//plugin settings and defaults
		public function bapl_getOptions() {	
			$this->do_migration();
			$getOptions = get_option('bapl');
			if (empty($getOptions)) {
				if ($this->_MU)
				$getOptions = get_site_option('bapl');
			}
			
			if (is_main_site())
				update_site_option('bapl', $getOptions);
			return $getOptions;
		}
		
		//settings page
		public function bapl_settings_page(){
			global $wp_roles;
			if ($this->_MU) {
				if (!current_user_can('manage_network'))  {wp_die( __('You do not have sufficient permissions to access this page.') );}
			}else{
				if (!current_user_can('manage_options'))  {wp_die( __('You do not have sufficient permissions to access this page.') );}	
			}
			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div> <h2><?php _e('Post Creation Limits'); ?> <a title="add a new limit rule" class="add-new-h2"><?php _e('Add New Limit'); ?></a></h2><br/><br/>
				<form method="post" action="options.php">
				<style>#TB_ajaxContent{height: 420px !important;}</style>
					<?php settings_fields('bapl_Options'); ?>
					<?php $options = $this->bapl_getOptions('bapl'); //get_option display: none?>
					<div id="list_limits">
						<table id="limit_rules" class="widefat">
							<thead>
								<tr>
									<th><?php _e('User Role/ID'); ?></th>
									<th><?php _e('Post Type'); ?></th>
									<th><?php _e('Limit'); ?></th>
									<th><?php _e('Post Status'); ?></th>
									<th><?php _e('Limit message'); ?></th>
									<th><?php _e('Time Span'); ?></th>
									<th><?php _e('Actions'); ?></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th><?php _e('User Role/ID'); ?></th>
									<th><?php _e('Post Type'); ?></th>
									<th><?php _e('Limit'); ?></th>
									<th><?php _e('Post Status'); ?></th>
									<th><?php _e('Limit message'); ?></th>
									<th><?php _e('Time Span'); ?></th>
									<th><?php _e('Actions'); ?></th>
								</tr>
							</tfoot>
							<tbody>
							<?php
							$c = 0;
							if (isset($options['rules'])){
								foreach($options['rules'] as $k => $v){
									echo '<tr>';
									echo '<td>'.$v['role'].'<input type="hidden" name="bapl[rules][' . $c . '][role]" value="'.$v['role'].'"></td>';
									echo '<td>'.$v['post_type'].'<input type="hidden" name="bapl[rules][' . $c . '][post_type]" value="'.$v['post_type'].'"></td>';
									echo '<td>'.$v['limit'].'<input type="hidden" name="bapl[rules][' .$c. '][limit]" value="'.$v['limit'].'"</td>';
									echo '<td>'.$v['status'].'<input type="hidden" name="bapl[rules][' . $c . '][status]" value="'.$v['status'].'"></td>';
									echo '<td><pre><code>'.htmlentities(substr($v['message'],0,32)).'</code></pre><textarea style="display:none;" name="bapl[rules][' . $c . '][message]">'.$v['message'].'</textarea></td>';
									echo '<td>'.((isset($v['time_span']) && $v['time_span'] != "FOREVER")? __('Every')." ".$v['time_span'].__(' hours'): (isset($v['time_span'])? $v['time_span'] : "FOREVER")) .'<input type="hidden" name="bapl[rules][' . $c . '][time_span]" value="'.($v['time_span']? $v['time_span'] : "FOREVER") .'"></td>';
									echo '<td><span class="edit_rule button-primary">Edit</span> <span class="remove_rule button-primary">Remove</span></td>';
									echo '</tr>';
									$c++;
								}
							}
							?>
							</tbody>
						</table>			
					</div>

					<?php //TODO: move to external file ?>
					<script type="text/javascript">
					var counter = <?php echo $c; ?>;
					var curr_row;
					function res_form(){
						jQuery('#ur').val('');
						jQuery('#pt').val('');
						jQuery('#lim').val('');
						jQuery('#st').val('');
						jQuery('#ms').val('');
						jQuery('.ro2').val('');
						jQuery('#rule_count').val('');
						jQuery('.save_edit').removeClass('save_edit').addClass('new_rule');
						jQuery('.new_rule').val('Add');
						jQuery('.ro').show();
						jQuery('.user_i').hide();
						jQuery('#tis').val('');
					}
					
					function add_new(){
						counter++;
						var tr = jQuery('<tr>');
						v1 = jQuery('#ur').val();
						if (v1 == 'USER_ID'){
							v1 = jQuery('#ro2').val();
						}
						v2 = jQuery('#pt').val();
						v3 = jQuery('#lim').val();
						v4 = jQuery('#st').val();
						v5 = jQuery('#ms').val();
						v6 = jQuery('#tis').val();
						tr.append('<td>'+ v1 +' <input type="hidden" name="bapl[rules][' + counter + '][role]" value="'+v1+'"></td>'); //<th>User Role/ID</th>
						tr.append('<td>'+ v2 +' <input type="hidden" name="bapl[rules][' + counter + '][post_type]" value="'+v2+'"></td>'); //<th>Post Type</th>
						tr.append('<td>'+ v3 +' <input type="hidden" name="bapl[rules][' + counter + '][limit]" value="'+v3+'"></td>'); //<th>Limit</th>
						tr.append('<td>'+ v4 +' <input type="hidden" name="bapl[rules][' + counter + '][status]" value="'+v4+'"></td>'); //<th>Post Status</th>
						tr.append('<td><pre><code>'+ html_entities(v5.substring(0, 35)) +'</code></pre> <textarea style="display:none;" name="bapl[rules][' + counter + '][message]">'+v5+'</textarea></td>'); //<th>Limit message</th>
						if (v6 == "FOREVER" || v6.length <= 0 || !isNumeric(v6))
							tr.append('<td><?php _e("FOREVER"); ?> <input type="hidden" name="bapl[rules][' + counter + '][time_span]" value="FOREVER"></td>');
						else
							tr.append('<td><?php _e("Every "); ?>'+v6+' <?php _e('hours');?> <input type="hidden" name="bapl[rules][' + counter + '][time_span]" value="'+v6+'"></td>');
						tr.append('<td><span class="edit_rule button-primary">Edit</span> <span class="remove_rule button-primary">Remove</span></td>'); //<th>Actions</th>
						jQuery('#limit_rules').find('tbody').append(tr);
						res_form();
						tb_remove();
					}
					
					//load edit
					function pre_edit_form(){
						res_form();
						v = new Array();
						jQuery('.new_rule').val('Save Edit');
						jQuery('.new_rule').removeClass('new_rule').addClass('save_edit');
						jQuery(curr_row).children().each(function(index, value) { 
							if ( index < 6){
								if (jQuery(value).find('input').length)
									v[index] = jQuery(value).find('input').val();
								else
									v[index] = jQuery(value).find('textarea').text();
							}
						});

						if (jQuery.isNumeric(v[0])){
							jQuery('#ur').val('USER_ID');
							jQuery('#ro2').val(v[0]);
							jQuery('.ro').hide();
							jQuery('.user_i').show();
						}else{
							jQuery('#ur').val(v[0]);	
						}
						
						jQuery('#pt').val(v[1]);
						jQuery('#lim').val(v[2]);
						jQuery('#st').val(v[3]);
						jQuery('#ms').val(v[4]);
						jQuery('#tis').val(v[5]);
						tb_show('Edit Limit Rule','TB_inline?height=420&width=400&inlineId=d_e_f');
						
					}
					
					//save edit function
					function r_save_edit(){
						var tr = jQuery('<tr>');
						v1 = jQuery('#ur').val();
						if (v1 == 'USER_ID'){
							v1 = jQuery('#ro2').val();
						}
						v2 = jQuery('#pt').val();
						v3 = jQuery('#lim').val();
						v4 = jQuery('#st').val();
						v5 = jQuery('#ms').val();
						v6 = jQuery('#tis').val();
						tr.append('<td>'+ v1 +' <input type="hidden" name="bapl[rules][' + counter + '][role]" value="'+v1+'"></td>'); //<th>User Role/ID</th>
						tr.append('<td>'+ v2 +' <input type="hidden" name="bapl[rules][' + counter + '][post_type]" value="'+v2+'"></td>'); //<th>Post Type</th>
						tr.append('<td>'+ v3 +' <input type="hidden" name="bapl[rules][' + counter + '][limit]" value="'+v3+'"></td>'); //<th>Limit</th>
						tr.append('<td>'+ v4 +' <input type="hidden" name="bapl[rules][' + counter + '][status]" value="'+v4+'"></td>'); //<th>Post Status</th>
						tr.append('<td><pre><code>'+ html_entities(v5) +'</code></pre>  <textarea style="display:none;" name="bapl[rules][' + counter + '][message]">'+v5+'</textarea></td>'); //<th>Limit message</th>
						if (v6 == "FOREVER" || v6.length <= 0 || !isNumeric(v6))
							tr.append('<td><?php _e("FOREVER"); ?> <input type="hidden" name="bapl[rules][' + counter + '][time_span]" value="FOREVER"></td>');
						else
							tr.append('<td><?php _e("Every "); ?>'+v6+' <?php _e('hours');?> <input type="hidden" name="bapl[rules][' + counter + '][time_span]" value="'+v6+'"></td>');
						tr.append('<td><span class="edit_rule button-primary">Edit</span> <span class="remove_rule button-primary">Remove</span></td>'); //<th>Actions</th>
						jQuery(curr_row).remove();
						jQuery('#limit_rules').find('tbody').append(tr);
						res_form();
						tb_remove();
					}

					//htmlentities
					function html_entities(str){
						encoded = jQuery('<div />').text(str).html();
						return encoded;
					}
					function isNumeric(n) {
						return !isNaN(parseFloat(n)) && isFinite(n);
					}
					//add new rule
					jQuery(".new_rule").live('click',function(e){
						e.preventDefault();
						add_new();
						return false;
					});
					//remove rule
					jQuery(".remove_rule").live('click',function(e){
						e.preventDefault();
						jQuery(this).parent().parent().remove();
						return false;
					});
					//row edit
					jQuery(".edit_rule").live('click',function(e){
						e.preventDefault();
						curr_row = jQuery(this).parent().parent()
						pre_edit_form();
					});
					//save edit
					jQuery(".save_edit").live('click',function(e){
						e.preventDefault();
						r_save_edit();
						return false;
					});
					
					//user id
					jQuery("#ur").live('change',function() {
						if (jQuery("#ur").val() == 'USER_ID'){
							jQuery('.ro').hide();
							jQuery('.user_i').show();
						}
					});
					
					//add new button-primary
					jQuery(".add-new-h2").live('click',function(){
						res_form();
						tb_show('add a new limit rule','TB_inline?height=420&width=400&inlineId=d_e_f');
					});
					</script>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</p>			
				</form>
				
				<div id="d_e_f" style="display: none">
					<br/>
					<form action="limit" id="limit_form" method="post" name="limiter">
						<ul><li class="ro">
							<label for="role"><?php _e('User Role'); ?></label>
							<select name="role" id="ur">
							<?php
								global $wp_roles;
								if ( !isset( $wp_roles ) )
									$wp_roles = new WP_Roles();
								foreach ( $wp_roles->role_names as $role => $name ) { ?>
									<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
									<?php
								}
							?>
								<option value="USER_ID"><?php _e('USER ID'); ?></option>
							</select><br/>
							<span class="dsc"><small><?php _e('Select a User Role'); ?></small></span>
						</li>
						<li class="user_i" style="display: none;">
							<label for="role"><?php _e('User ID'); ?></label>
							<input name="ro2" type="text" id="ro2" size="4"/><br/>
							<span class="dsc"><small><?php _e('Enter User ID to limit'); ?></small></span>
						</li>
						<li>
							<label for="ptype"><?php _e('Post Type'); ?></label>
							<select name="ptype" id="pt">
								<option value="any">Any</option>
							<?php
								$post_types=get_post_types('','names'); 
								foreach ($post_types as $post_type ) {
									?>
									<option value="<?php echo $post_type;?>"><?php echo $post_type;?></option>
									<?php
								}
							?>
							</select><br/>
							<span class="dsc"><small><?php _e('Select a Post Type'); ?></small></span>
						</li>
						<li>
							<label for="limit"><?php _e('Limit'); ?></label>
							<input name="limit" type="text" id="lim" size="4"/><br/>
							<span class="dsc"><small><?php _e('Select a user role'); ?></small></span>
						</li>
						<li>
							<label for="Status"><?php _e('Status'); ?></label>
							<select name="Status" id="st">
								<option value="any">any</option>
								<option value="publish">publish</option>
								<option value="pending">pending</option>
								<option value="draft">draft</option>
								<option value="auto-draft">auto-draft</option>
								<option value="future">future</option>
								<option value="private">private</option>
								<option value="inherit">inherit</option>
								<option value="trash">trash</option>
							</select><br/>
							<span class="dsc"><small><?php _e('Select Post Status to count'); ?></small></span>
						</li>
						<li>
							<label for="message"><?php _e('Limit Message'); ?></label><br/>
							<textarea name="message" id="ms"></textarea><br/>
							<span class="dsc"><small><?php _e('Enter limit message'); ?></small></span>
						</li>
						<li>
							<label for="time_span"><?php _e('Time Span'); ?></label>
							<input name="time_span" type="text" id="tis" size="4"/><br/>
							<span class="dsc"><small><?php _e('How long to Limit The User In hours (or you can type FOREVER)'); ?></small></span>
						</li>
						<input type="hidden" id="rule_count" value=""/>
					</ul>
					<input type='submit' value='<?php _e('add'); ?>' class='button-primary new_rule' />
					</form>
				</div>
			</div>
			<?php
		}

		// display error massage
		public function bapl_not_allowed($m=null){
			if ($m == null){
				$options = $this->bapl_getOptions('bapl');
				$m = $options['m'];
			}
			?>
			<style>
			html {background: #f9f9f9;}
			#error-page {margin-top: 50px;}
			#error-page p {font-size: 14px;line-height: 1.5;margin: 25px 0 20px;}
			#error-page code {font-family: Consolas, Monaco, monospace;}
			body {background: #fff;color: #333;font-family: sans-serif;margin: 2em auto;padding: 1em 2em;-webkit-border-radius: 3px;border-radius: 3px;border: 1px solid #dfdfdf;max-width: 700px;height: auto;}
			</style>
			<div id="error-page">
				<div id="message" class="<?php echo apply_filters('post_creation_limits_limited_message_class','error'); ?>" style="padding: 10px;"><?php echo apply_filters('bapl_limited_message_Filter',$m); ?></div>
			</div>
			
			<?php
			do_action('post_creation_limits_after_limited_message');
		}
		
		/************************
		* helpers
		************************/
		//get user role
		public function balp_get_current_user_role() {
			global $wp_roles;
			$current_user = wp_get_current_user();
			$roles = $current_user->roles;
			$role = array_shift($roles);
			return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role] ) : false;
		}
		
		//get sub array where key exists
		public function get_sub_array($Arr,$key,$val){
			$new_arr = array();
			foreach((array)$Arr as $k => $v){
				if (isset($v[$key]) && $v[$key] == $val)
					$new_arr[] = $v;
			}
			if (count($new_arr) > 0 )
				return $new_arr;
			return false;
		}
		
		
		//sub value array sort
		public function subval_sort($a,$subkey) {
			foreach((array)$a as $k=>$v) {
				$b[$k] = strtolower($v[$subkey]);
			}
			asort($b);
			foreach((array)$b as $key=>$val) {
				$c[] = $a[$key];
			}
			return $c;
		}
		
		
		//migrate from old install
		public function do_migration(){
			$options = get_option('bapl');
			if (isset($options['done_migration'])){
				return;
			}
			$post_types=get_post_types('','names'); 
			foreach ((array)$post_types as $post_type ) {
				if (isset($options[$post_type]) && $options[$post_type] != 0) {
					$new_rule = array(
						'role' => 'ANY',
						'post_type' => $post_type,
						'limit' => $options[$post_type],
						'status' => 'publish',
						'message' => $options['m']
					);
					$options['rules'][] = $new_rule;
					unset($options[$post_type]);
				}
			}
			
			if ( !isset( $wp_roles ) )
				$wp_roles = new WP_Roles();
			foreach ( $wp_roles->role_names as $role => $name ) {
				foreach ((array)$post_types as $post_type ) {
					if (isset($options[$name][$post_type]) && $options[$name][$post_type] != 0) {
						$new_rule = array(
							'role' => $name,
							'post_type' => $post_type,
							'limit' => $options[$name][$post_type],
							'status' => 'publish',
							'message' => $options['m']
						);
						$options['rules'][] = $new_rule;
						unset($options[$name][$post_type]);
					}
				}
			}
			$options['done_migration'] = true;
			update_option('bapl',$options);
		}
	}//end class
}//end if class exists

global $bapl;
$bapl = new bapl();