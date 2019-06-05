<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');
/*
============================================================
 Created by Leon Dijk
 - http://gwcode.com/
------------------------------------------------------------
 This plugin is licensed under The BSD 3-Clause License.
 - http://www.opensource.org/licenses/bsd-3-clause
============================================================
*/

class Gwcode_categories {

	public $return_data = '';
	private $gw_tagdata = '';
	private $categories = array();
	private $categories_final = array();
	private $multi_depth = '';
	private $group_id_arr = array(); // array with category group information.
	private $site_ids = ''; // comma separated string of site id's to be used in sql queries.
	private $channel_ids = ''; // comma separated string of channel id's to be used in sql queries.
	private $group_ids = ''; // comma separated string of group id's to be used in sql queries.
	private $entry_ids = ''; // comma separated string of entry id's to be used in sql queries.
	private $custom_fields_arr = array(); // array with custom category field id's and names. key = group_id; value = array
	private $sql_type = 3; // 1 --> entry count, with exp_channel_titles table; 2 --> entry_count (simpler); 3 --> no entry count.
	private $remove_from_begin = 0;
	private $remove_from_end = 0;
	private $max_depth_in_output = 1; // this will store the highest depth number in the final output
	private $min_depth_in_output = 1; // this will store the lowest depth number in the final output

	public function __construct() {
		$this->EE = get_instance();

		// start: fetch & validate plugin params
		$this->site_id = $this->EE->TMPL->fetch_param('site_id', 1);
		$this->site_ids = str_replace('|', ',', $this->site_id);
		if(!is_numeric(str_replace(',', '', $this->site_ids))) {
			$this->EE->TMPL->log_item('Error: the "site_id" parameter value needs to be numeric or numeric separated by pipe characters.');
			return;
		}
		$this->channel = $this->EE->TMPL->fetch_param('channel');
		$this->group_id = $this->EE->TMPL->fetch_param('group_id');
		if(!empty($this->group_id) && !is_numeric(str_replace('|', '', $this->group_id))) {
			$this->EE->TMPL->log_item('Error: the "group_id" parameter value needs to be numeric or numeric separated by pipe characters.');
			return;
		}
		$this->entry_id = $this->EE->TMPL->fetch_param('entry_id'); // validate later
		$this->limit = $this->EE->TMPL->fetch_param('limit');
		if(!empty($this->limit) && !is_numeric($this->limit)) {
			$this->EE->TMPL->log_item('Error: the "limit" parameter value needs to be numeric.');
			return;
		}
		$this->last_only = strtolower($this->EE->TMPL->fetch_param('last_only'));
		if(!empty($this->last_only) && $this->last_only != 'no' && $this->last_only != 'yes') {
			$this->EE->TMPL->log_item('Error: the "last_only" parameter value needs to be either "yes" or "no".');
			return;
		}
		$this->style = strtolower($this->EE->TMPL->fetch_param('style','nested'));
		if($this->style != 'nested' && $this->style != 'linear' && $this->style != 'simple') {
			$this->EE->TMPL->log_item('Error: the "style" parameter value needs to be either "nested", "linear" or "simple".');
			return;
		}
		$this->list_type = strtolower($this->EE->TMPL->fetch_param('list_type','ul'));
		if($this->list_type != 'ul' && $this->list_type != 'ol') {
			$this->EE->TMPL->log_item('Error: the "list_type" parameter value needs to be either "ul" or "ol".');
			return;
		}
		$this->backspace = $this->EE->TMPL->fetch_param('backspace');
		if(!empty($this->backspace) && !is_numeric($this->backspace)) {
			$this->EE->TMPL->log_item('Error: the "backspace" parameter value needs to be numeric.');
			return;
		}
		if(!empty($this->backspace) && $this->style != 'linear') {
			$this->EE->TMPL->log_item('Warning: the "backspace" parameter can only be used in combination with style="linear".');
		}
		$this->id = $this->EE->TMPL->fetch_param('id');
		$this->class = $this->EE->TMPL->fetch_param('class');
		$this->depth = $this->EE->TMPL->fetch_param('depth');
		if(!empty($this->depth) && !is_numeric(str_replace('|', '', $this->depth))) {
			$this->EE->TMPL->log_item('Error: the "depth" parameter value needs to be numeric or numeric separated by pipe characters.');
			return;
		}
		$this->min_depth = $this->EE->TMPL->fetch_param('min_depth');
		if(!empty($this->min_depth) && !is_numeric($this->min_depth)) {
			$this->EE->TMPL->log_item('Error: the "min_depth" parameter value needs to be numeric.');
			return;
		}
		$this->max_depth = $this->EE->TMPL->fetch_param('max_depth');
		if(!empty($this->max_depth) && !is_numeric($this->max_depth)) {
			$this->EE->TMPL->log_item('Error: the "max_depth" parameter value needs to be numeric.');
			return;
		}
		$this->entry_count = strtolower($this->EE->TMPL->fetch_param('entry_count','no'));
		if($this->entry_count != 'yes' && $this->entry_count != 'no') {
			$this->EE->TMPL->log_item('Error: the "entry_count" parameter value needs to be either "yes" or "no".');
			return;
		}
		$this->status = $this->EE->TMPL->fetch_param('status');
		$this->show_empty = strtolower($this->EE->TMPL->fetch_param('show_empty','yes'));
		if($this->show_empty != 'yes' && $this->show_empty != 'no') {
			$this->EE->TMPL->log_item('Error: the "show_empty" parameter value needs to be either "yes" or "no".');
			return;
		}
		$this->cat_id = $this->EE->TMPL->fetch_param('cat_id'); // validate later
		$this->cat_url_title = $this->EE->TMPL->fetch_param('cat_url_title'); // multiple category groups could have a category with the same cat_url_title, so the group_id parameter can be used as well to limit the search in a certain category group
		$this->show_trail = strtolower($this->EE->TMPL->fetch_param('show_trail','no'));
		if($this->show_trail != 'yes' && $this->show_trail != 'no') {
			$this->EE->TMPL->log_item('Error: the "show_trail" parameter value needs to be either "yes" or "no".');
			return;
		}
		if(empty($this->cat_id) && empty($this->cat_url_title) && $this->EE->TMPL->fetch_param('show_trail')) {
			$this->EE->TMPL->log_item('Error: the "show_trail" parameter can only be used in combination with the "cat_id" or "cat_url_title" parameter.');
			return;
		}
		$this->incl_self = strtolower($this->EE->TMPL->fetch_param('incl_self','yes'));
		if($this->incl_self != 'yes' && $this->incl_self != 'no') {
			$this->EE->TMPL->log_item('Error: the "incl_self" parameter value needs to be either "yes" or "no".');
			return;
		}
		if(empty($this->cat_id) && empty($this->cat_url_title) && $this->EE->TMPL->fetch_param('incl_self')) {
			$this->EE->TMPL->log_item('Error: the "incl_self" parameter can only be used in combination with the "cat_id" or "cat_url_title" parameter.');
			return;
		}
		$this->custom_fields = strtolower($this->EE->TMPL->fetch_param('custom_fields','no'));
		if($this->custom_fields != 'yes' && $this->custom_fields != 'no') {
			$this->EE->TMPL->log_item('Error: the "custom_fields" parameter value needs to be either "yes" or "no".');
			return;
		}
		$this->show_full_trail = strtolower($this->EE->TMPL->fetch_param('show_full_trail','no'));
		if($this->show_full_trail != 'yes' && $this->show_full_trail != 'no') {
			$this->EE->TMPL->log_item('Error: the "show_full_trail" parameter value needs to be either "yes" or "no".');
			return;
		}
		if(empty($this->entry_id) && $this->EE->TMPL->fetch_param('show_full_trail')) {
			$this->EE->TMPL->log_item('Error: the "show_full_trail" parameter can only be used in combination with the "entry_id" parameter.');
			return;
		}
		$this->excl_cat_id = $this->EE->TMPL->fetch_param('excl_cat_id');
		if(!empty($this->excl_cat_id) && !is_numeric(str_replace('|', '', $this->excl_cat_id))) {
			$this->EE->TMPL->log_item('Error: the "excl_cat_id" parameter value needs to be numeric or numeric separated by pipe characters.');
			return;
		}
		$this->excl_cat_id_children = $this->EE->TMPL->fetch_param('excl_cat_id_children','no');
		if($this->excl_cat_id_children != 'yes' && $this->excl_cat_id_children != 'no') {
			$this->EE->TMPL->log_item('Error: the "excl_cat_id_children" parameter value needs to be either "yes" or "no".');
			return;
		}
		if(empty($this->excl_cat_id) && $this->EE->TMPL->fetch_param('excl_cat_id_children')) {
			$this->EE->TMPL->log_item('Warning: the "excl_cat_id_children" parameter can only be used in combination with the "excl_cat_id" parameter.');
		}
		$this->count_future_entries = $this->EE->TMPL->fetch_param('count_future_entries','yes');
		if($this->count_future_entries != 'yes' && $this->count_future_entries != 'no') {
			$this->EE->TMPL->log_item('Error: the "count_future_entries" parameter value needs to be either "yes" or "no".');
			return;
		}
		$this->count_expired_entries = $this->EE->TMPL->fetch_param('count_expired_entries','yes');
		if($this->count_expired_entries != 'yes' && $this->count_expired_entries != 'no') {
			$this->EE->TMPL->log_item('Error: the "count_expired_entries" parameter value needs to be either "yes" or "no".');
			return;
		}
		$this->output_depth = $this->EE->TMPL->fetch_param('output_depth');
		if(!empty($this->output_depth) && !is_numeric(str_replace('|', '', $this->output_depth))) {
			$this->EE->TMPL->log_item('Error: the "output_depth" parameter value needs to be numeric or numeric separated by pipe characters.');
			return;
		}
		$this->orderby = strtolower($this->EE->TMPL->fetch_param('orderby'));
		$this->sort = strtolower($this->EE->TMPL->fetch_param('sort','asc'));
		if(!empty($this->orderby)) {
			$orderby_arr = explode('|',$this->orderby);
			$orderby_count = count($orderby_arr);
			$sort_arr = explode('|',$this->sort);
			$allowed_orderby_values = array('entry_count','cat_id','cat_name','random');
			foreach($orderby_arr as $key => $value) {
				if(!in_array($value,$allowed_orderby_values)) {
					$this->EE->TMPL->log_item('Error: the "orderby" parameter value needs to contain "entry_count", "cat_id", "cat_name" or "random".');
					return;
				}
				if($value == 'random') {
					if($this->style == 'nested') {
						$this->EE->TMPL->log_item('Error: when using "random" as the "orderby" parameter value, the "style" parameter needs to be "simple" or "linear".');
						return;
					}
					if($orderby_count > 1) {
						$this->EE->TMPL->log_item('Error: you can\'t use multiple values for the "orderby" parameter when using "random".');
						return;
					}
				}
				if(!isset($sort_arr[$key])) {
					$sort_arr[$key] = 'asc';
				}
				else {
					if($sort_arr[$key] != 'asc' && $sort_arr[$key] != 'desc') {
						$this->EE->TMPL->log_item('Error: the "sort" parameter value needs to be "asc" or "desc".');
						return;
					}
				}
			}
			$this->sort = implode('|',$sort_arr); // this variable has the same number of values as $this->orderby now.
		}
		$this->offset = $this->EE->TMPL->fetch_param('offset');
		if(!empty($this->offset)) {
			$offset_count = 0;
			$off_negative_found = $off_positive_found = false;
			$offset_arr = explode('|',$this->offset);
			foreach($offset_arr as $key => $value) {
				if(!is_numeric($value)) {
					$this->EE->TMPL->log_item('Error: the "offset" parameter value(s) needs to be numeric.');
					return;
				}
				if($value[0] == '-') {
					if($off_negative_found) {
						$this->EE->TMPL->log_item('Error: you can only provide 1 negative "offset" parameter value.');
						return;
					}
					$off_negative_found = true;
					$this->remove_from_begin = abs($value);
				}
				else {
					if($off_positive_found) {
						$this->EE->TMPL->log_item('Error: you can only provide 1 positive "offset" parameter value.');
						return;
					}
					$off_positive_found = true;
					$this->remove_from_end = $value;
				}
				$offset_count++;
			}
			if(count($offset_count) > 2) {
				$this->EE->TMPL->log_item('Error: you can only provide one or two values for the "offset" parameter.');
				return;
			}
		}
		$this->excl_group_id = $this->EE->TMPL->fetch_param('excl_group_id');
		if(!empty($this->excl_group_id) && !is_numeric(str_replace('|', '', $this->excl_group_id))) {
			$this->EE->TMPL->log_item('Error: the "excl_group_id" parameter value needs to be numeric or numeric separated by pipe characters.');
			return;
		}
		if(empty($this->channel) && $this->EE->TMPL->fetch_param('excl_group_id')) {
			$this->EE->TMPL->log_item('Error: the "excl_group_id" parameter can only be used in combination with the "channel" parameter.');
			return;
		}
		$this->var_prefix = $this->EE->TMPL->fetch_param('variable_prefix', '');
		$this->show = $this->EE->TMPL->fetch_param('show');
		if(!empty($this->show) && !is_numeric(str_replace('|', '', $this->show))) {
			$this->EE->TMPL->log_item('Error: the "show" parameter value needs to be numeric or numeric separated by pipe characters.');
			return;
		}
		$this->switch = $this->EE->TMPL->fetch_param('switch');
		// end: fetch & validate plugin params

		$this->multi_depth = (strpos($this->depth,'|') !== false) ? true : false;

		if($this->show_empty == 'no' || $this->entry_count == 'yes' || $this->EE->TMPL->fetch_param('count_future_entries') || $this->EE->TMPL->fetch_param('count_expired_entries')) {
			// if the channel parameter has been set and not the group_id parameter, we need to make sure it only counts entries for those channels (look in exp_channel_titles for the channel_id) --> sql_type: 1
			$this->sql_type = (!empty($this->channel) && !$this->EE->TMPL->fetch_param('group_id')) ? 1 : 2;
		}
		if($this->status != '' || $this->count_future_entries == 'no' || $this->count_expired_entries == 'no') {
			$this->sql_type = 1;
		}

		$this->gw_tagdata = $this->EE->TMPL->tagdata;
		$mode = (trim($this->gw_tagdata)) ? 'pair' : 'single';
		if($mode == 'single') { // add default tagdata --> all category groups and their categories
			if($this->style != 'linear') {
				$this->gw_tagdata = LD.'group_heading'.RD.'<h2 class="gwcode_categories">'.LD.$this->var_prefix.'cat_group_name'.RD.'</h2>'.LD.'/group_heading'.RD.LD.$this->var_prefix.'cat_name'.RD;
			}
			else {
				$this->gw_tagdata .= LD.'if '.$this->var_prefix.'group_start'.RD.'<h2 class="gwcode_categories">'.LD.$this->var_prefix.'cat_group_name'.RD.'</h2>'.LD.'/if'.RD.LD.'if '.$this->var_prefix.'group_end'.RD.LD.$this->var_prefix.'cat_name'.RD.LD.'if:else'.RD.LD.$this->var_prefix.'cat_name'.RD.', '.LD.'/if'.RD;
			}
		}

		if(!empty($this->entry_id)) {
			// get category trail for an entry. The group_id parameter can be provided as well to only show categories from those groups!
			if(!is_numeric(str_replace('|', '', $this->entry_id))) {
				$this->EE->TMPL->log_item('Error: the "entry_id" parameter value needs to be numeric or numeric separated by pipe characters.');
				return;
			}
			$this->entry_ids = implode(',',array_filter(explode('|',$this->entry_id)));
			$this->return_data = $this->_get_by_entry_id();
		}
		elseif(!empty($this->cat_id) || (!empty($this->cat_url_title))) {
			// get a category's children, or show a trail from the root category to the category as a breadcrumb trail.
			if(!is_numeric($this->site_id)) {
				$this->EE->TMPL->log_item('Error: you can only provide a single site ID in the "site_id" parameter when used in combination with the "cat_id" or "cat_url_title" parameter.');
				return;
			}
			if(!empty($this->cat_id)) {
				if(!is_numeric($this->cat_id)) {
					$this->EE->TMPL->log_item('Error: the "cat_id" parameter value needs to be numeric and you can only provide a single category ID.');
					return;
				}
				// if the channel parameter has been set as well, we're going to count entries for this channel only
				$this->return_data = (!empty($this->channel)) ? $this->_get_by_channel() : $this->_get_by_cat();
			}
			else {
				// if the channel parameter has been set as well, we're going to count entries for this channel only
				$this->return_data = (!empty($this->channel)) ? $this->_get_by_channel() : $this->_get_by_cat(4);
			}
		}
		elseif(!empty($this->channel)) {
			// get categories for all category groups for the channel(s), plus the optional extra ones provided with the group_id parameter.
			$this->return_data = $this->_get_by_channel();
		}
		elseif(!empty($this->group_id)) {
			// get categories for 1 or more category group
			$this->return_data = $this->_get_by_group_id();
		}
		else {
			// get categories for all category groups
			$this->return_data = $this->_get_by_group_id(3);
		}
	} // end function __construct

	private function _get_by_channel() {
		$channels = $this->EE->db->escape_str($this->channel);
		$channels = str_replace('|', "','", $channels);
		$gwc_result = $this->EE->db->query('SELECT channel_id, cat_group FROM exp_channels WHERE site_id IN('.$this->EE->db->escape_str($this->site_ids).') AND channel_name IN (\''.$channels.'\') ORDER BY FIELD(channel_name, \''.$channels.'\')');
		if($gwc_result->num_rows() == 0) {
			return $this->EE->TMPL->no_results();
		}
		$group_arr = $channel_id_arr = array();
		foreach($gwc_result->result_array() as $row) {
			$group_arr = array_merge($group_arr,array_filter(explode('|',$row['cat_group']))); // using array_filter here since the data could look like "|5|6" for example when "None" has been selected as a category group for a channel.
			$channel_id_arr[] = $row['channel_id'];
		}
		if(!empty($this->excl_group_id)) {
			// getting category groups for a channel, but not all of them
			$excl_group_id_arr = explode('|', $this->excl_group_id);
			foreach($group_arr as $group_arr_key => $group_arr_value) {
				if(in_array($group_arr_value, $excl_group_id_arr)) {
					unset($group_arr[$group_arr_key]);
				}
			}
			$group_arr = array_values($group_arr);
		}
		if(!empty($this->group_id)) { // add additional groups provided with group_id parameter
			$group_arr = array_merge($group_arr,explode('|',$this->group_id));
		}
		if(empty($group_arr)) {
			return $this->EE->TMPL->no_results();
		}
		$this->group_id = implode('|',$group_arr);
		$this->channel_ids = implode(',',array_filter($channel_id_arr));
		if(!empty($this->cat_id)) {
			return $this->_get_by_cat();
		}
		elseif(!empty($this->cat_url_title)) {
			return $this->_get_by_cat(4);
		}
		else {
			return $this->_get_by_group_id();
		}
	} // end function _get_by_channel

	private function _get_by_group_id($sqltype=null) {
		$cat_array = array();

		// first grab the category group information we need. Makes $this->group_ids available for use
		if(!$this->_get_cat_group_info($sqltype)) {
			return $this->EE->TMPL->no_results(); // no category groups were found
		}

		if($this->custom_fields == 'yes') {
			// load typography library and grab info for custom category fields. Makes $this->custom_fields_arr available for use
			$this->_get_custom_fields();
		}

		// grab all categories from the category groups we've collected

		if($this->sql_type == 1) { // most advanced query
			$status = $this->EE->db->escape_str($this->status);
			$status = str_replace('|', "','", $status);
			$timestamp = time();
			$sql_extra = '';
			if(!empty($this->channel) && !$this->EE->TMPL->fetch_param('group_id')) {
				// if the channel parameter has been set and not the group_id parameter, we need to make sure it only counts entries for those channels (the channel parameter acts as a filter in that case). This is because category groups can be assigned to several channels.
				$sql_extra .= ' AND channel_id IN ('.$this->EE->db->escape_str($this->channel_ids).')';
			}
			if($status != '') {
				$sql_extra .= ' AND ct.status IN (\''.$status.'\')';
			}
			if($this->count_future_entries == 'no') {
				$sql_extra .= ' AND ct.entry_date < '.$timestamp;
			}
			if($this->count_expired_entries == 'no') {
				$sql_extra .= ' AND (ct.expiration_date = 0 OR ct.expiration_date > '.$timestamp.')';
			}

			if($this->custom_fields == 'no') {
				$sql =	'SELECT c.site_id, c.cat_id, c.group_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.cat_order, ' .
						'(' .
						'SELECT COUNT(ct.entry_id) ' .
						'FROM exp_channel_titles ct, exp_category_posts cp ' .
						'WHERE cp.entry_id=ct.entry_id AND ct.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND cp.cat_id=c.cat_id';
				$sql .=	$sql_extra;
				$sql .=	') AS entry_count ' .
						'FROM exp_categories c ' .
						'WHERE c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') AND c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') ' .
						'ORDER BY site_id, FIELD(c.group_id, '.$this->EE->db->escape_str($this->group_ids).'), parent_id, cat_order';
			}
			else {
				$sql =	'SELECT c.parent_id, c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.cat_order, cfd.*, ' .
						'(' .
						'SELECT COUNT(ct.entry_id) ' .
						'FROM exp_channel_titles ct, exp_category_posts cp ' .
						'WHERE cp.entry_id=ct.entry_id AND ct.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND cp.cat_id=c.cat_id';
				$sql .=	$sql_extra;
				$sql .=	') AS entry_count ' .
						'FROM exp_categories c LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id=cfd.site_id AND c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') AND c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') ' .
						'ORDER BY site_id, FIELD(c.group_id, '.$this->EE->db->escape_str($this->group_ids).'), parent_id, cat_order';
			}
		}
		elseif($this->sql_type == 2) { // simpler query
			if($this->custom_fields == 'no') {
				$sql =	'SELECT site_id, c.cat_id, group_id, parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, COUNT(cp.entry_id) AS entry_count ' .
						'FROM exp_categories c ' .
						'LEFT JOIN exp_category_posts as cp ON c.cat_id=cp.cat_id ' .
						'WHERE group_id IN ('.$this->EE->db->escape_str($this->group_ids).') AND site_id IN ('.$this->EE->db->escape_str($this->site_ids).') ' .
						'GROUP BY c.cat_id ORDER BY site_id, FIELD(group_id, '.$this->EE->db->escape_str($this->group_ids).'), parent_id, cat_order';
			}
			else {
				$sql =	'SELECT c.parent_id, c.cat_name, cat_url_title, cat_description, cat_image, cat_order, COUNT(cp.entry_id) AS entry_count, cfd.* ' .
						'FROM exp_categories c ' .
						'LEFT JOIN exp_category_posts as cp ON c.cat_id=cp.cat_id ' .
						'LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id=cfd.site_id ' .
						'AND c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') AND c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') ' .
						'GROUP BY c.cat_id ORDER BY site_id, FIELD(c.group_id, '.$this->EE->db->escape_str($this->group_ids).'), parent_id, cat_order';
			}
		}
		else { // simplest query, no need to count entries
			if($this->custom_fields == 'no') {
				$sql = 'SELECT * FROM exp_categories WHERE site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ORDER BY site_id, FIELD(group_id, '.$this->EE->db->escape_str($this->group_ids).'), parent_id, cat_order';
			}
			else {
				$sql =	'SELECT c.parent_id, c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.cat_order, cfd.* ' .
						'FROM exp_categories c LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id=cfd.site_id AND c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ORDER BY site_id, FIELD(c.group_id, '.$this->EE->db->escape_str($this->group_ids).'), parent_id, cat_order';
			}
		}
		if(!$this->_get_categories($sql)) {
			return $this->EE->TMPL->no_results(); // no categories were found
		}
		// $this->categories is now available and in the correct order.

		// clean up array
		$this->categories = array_values($this->categories);

		if($this->last_only == 'yes') {
			// remove all but last child categories
			$this->_remove_not_last_child_categories();
		}

		if(!empty($this->depth) || !empty($this->min_depth) || !empty($this->max_depth)) {
			// we may need to remove categories based on the depth params
			$this->_remove_categories_by_depth();
		}

		if($this->show_empty == 'no') {
			// remove categories that have no entries
			$this->_remove_empty_categories();
		}

		if(!empty($this->excl_cat_id)) {
			// remove categories and potential subcategories that have been provided with the excl_cat_id parameter
			$remove_children = ($this->excl_cat_id_children == 'yes') ? true : false;
			$this->_exclude_categories($remove_children);
		}

		if(!empty($this->show)) {
			// remove categories not provided with the show parameter
			$this->_remove_not_in_show();
		}

		if(!empty($this->orderby)) {
			$this->_sort_results();
		}

		if(!empty($this->output_depth)) {
			// let's filter categories by output depth.
			$this->_remove_by_output_depth();
		}

		// process offset parameter
		if(!empty($this->remove_from_begin)) {
			$this->categories = array_splice($this->categories, $this->remove_from_begin);
		}
		if(!empty($this->remove_from_end)) {
			array_splice($this->categories, -$this->remove_from_end);
		}

		return $this->_generate_output();
	} // end function _get_by_group_id

	private function _get_by_cat($cat_group_info_param=1) {
		$cat_array = array();

		// first grab the category group information we need. Makes $this->group_ids available for use
		if(!$this->_get_cat_group_info($cat_group_info_param)) {
			return $this->EE->TMPL->no_results(); // no category groups were found
		}

		if($this->custom_fields == 'yes') {
			// load typography library and grab info for custom category fields. Makes $this->custom_fields_arr available for use
			$this->_get_custom_fields();
		}

		// grab all categories from the category groups we've collected

		if($this->sql_type == 1) { // most advanced query
			$status = $this->EE->db->escape_str($this->status);
			$status = str_replace('|', "','", $status);
			$timestamp = time();
			$sql_extra = '';
			if(!empty($this->channel) && !$this->EE->TMPL->fetch_param('group_id')) {
				// if the channel parameter has been set and not the group_id parameter, we need to make sure it only counts entries for those channels (the channel parameter acts as a filter in that case). This is because category groups can be assigned to several channels.
				$sql_extra .= ' AND channel_id IN ('.$this->EE->db->escape_str($this->channel_ids).')';
			}
			if($status != '') {
				$sql_extra .= ' AND ct.status IN (\''.$status.'\')';
			}
			if($this->count_future_entries == 'no') {
				$sql_extra .= ' AND ct.entry_date < '.$timestamp;
			}
			if($this->count_expired_entries == 'no') {
				$sql_extra .= ' AND (ct.expiration_date = 0 OR ct.expiration_date > '.$timestamp.')';
			}

			if($this->custom_fields == 'no') {
				$sql =	'SELECT c.*, ' .
						'(' .
						'SELECT COUNT(ct.entry_id) ' .
						'FROM exp_channel_titles ct, exp_category_posts cp ' .
						'WHERE cp.entry_id=ct.entry_id AND cp.cat_id=c.cat_id';
				$sql .=	$sql_extra;
				$sql .=	') AS entry_count ' .
						'FROM exp_categories c ' .
						'WHERE c.site_id IN('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN('.$this->EE->db->escape_str($this->group_ids).') ' .
						'ORDER BY parent_id, cat_order';
			}
			else {
				$sql =	'SELECT parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, ' .
						'(' .
						'SELECT COUNT(ct.entry_id) ' .
						'FROM exp_channel_titles ct, exp_category_posts cp ' .
						'WHERE cp.entry_id=ct.entry_id AND cp.cat_id=c.cat_id';
				$sql .=	$sql_extra;
				$sql .=	') AS entry_count, cfd.* ' .
						'FROM exp_categories c ' .
						'LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id=cfd.site_id AND c.site_id IN('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN('.$this->EE->db->escape_str($this->group_ids).') ' .
						'ORDER BY parent_id, cat_order';
			}
		}
		elseif($this->sql_type == 2) { // simpler query
			if($this->custom_fields == 'no') {
				$sql =	'SELECT c.*, COUNT(cp.entry_id) AS entry_count ' .
						'FROM exp_categories c LEFT JOIN exp_category_posts as cp ON c.cat_id=cp.cat_id ' .
						'WHERE c.site_id IN('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN('.$this->EE->db->escape_str($this->group_ids).') ' .
						'GROUP BY c.cat_id ORDER BY parent_id, cat_order';
			}
			else {
				$sql =	'SELECT parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, COUNT(cp.entry_id) AS entry_count, cfd.* ' .
						'FROM exp_categories c LEFT JOIN exp_category_posts as cp ON c.cat_id=cp.cat_id ' .
						'LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id=cfd.site_id AND c.site_id IN('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN('.$this->EE->db->escape_str($this->group_ids).') ' .
						'GROUP BY c.cat_id ORDER BY parent_id, cat_order ';
			}
		}
		else { // simplest query, no need to count entries
			if($this->custom_fields == 'no') {
				$sql = 'SELECT * FROM exp_categories WHERE site_id IN('.$this->EE->db->escape_str($this->site_ids).') AND group_id IN('.$this->EE->db->escape_str($this->group_ids).') ORDER BY parent_id, cat_order';
			}
			else {
				$sql = 'SELECT parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, cfd.* FROM exp_categories c LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id WHERE c.site_id=cfd.site_id AND c.site_id IN('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN('.$this->EE->db->escape_str($this->group_ids).') ORDER BY parent_id, cat_order';
			}
		}
		if(!$this->_get_categories($sql)) {
			return $this->EE->TMPL->no_results(); // no categories were found
		}
		// $this->categories is now available and in the correct order.

		$get_cats_children = ($this->show_trail == 'yes') ? false : true;
		$keep_cat_id_arr = array(); // an array with category id's that we need to keep

		if(!$get_cats_children) { // show breadcrumb trail
			$keep_cat_id_arr = $this->categories[$this->cat_id]['complete_path'];
			if($this->incl_self == 'no') {
				unset($keep_cat_id_arr[$this->cat_id]);
			}
			$keep_cat_id_arr = array_keys($keep_cat_id_arr);
		}
		else { // show category children
			foreach($this->categories as $key => $val) {
				if($key == $this->cat_id) { // we're getting all children from the supplied cat_id
					$get_cats_children_depth = $val['depth'];
					if($this->incl_self == 'yes') {
						$keep_cat_id_arr[] = $key;
					}
				}
				else {
					if(isset($get_cats_children_depth)) {
						if($val['depth'] <= $get_cats_children_depth) {
							break;
						}
						$keep_cat_id_arr[] = $key;
					}
				}
			}
		}

		// clean up array
		$this->categories = array_values($this->categories);

		if($this->last_only == 'yes') {
			// remove all but last child categories
			$this->_remove_not_last_child_categories();
		}

		if(!empty($this->depth) || !empty($this->min_depth) || !empty($this->max_depth)) {
			// we may need to remove categories based on the depth params
			$this->_remove_categories_by_depth();
		}

		// the $keep_cat_id_arr array has all the categories we need, delete the rest
		foreach($this->categories as $key => $val) {
			if(!in_array($val['cat_id'],$keep_cat_id_arr)) {
				unset($this->categories[$key]);
			}
		}

		// clean up array
		$this->categories = array_values($this->categories);

		if($this->show_empty == 'no') {
			// remove categories that have no entries
			$this->_remove_empty_categories();
		}

		if(!empty($this->excl_cat_id)) {
			// remove categories and potential subcategories that have been provided with the excl_cat_id parameter
			$remove_children = ($this->excl_cat_id_children == 'yes') ? true : false;
			$this->_exclude_categories($remove_children);
		}

		if(!empty($this->show)) {
			// remove categories not provided with the show parameter
			$this->_remove_not_in_show();
		}

		if(!empty($this->orderby)) {
			$this->_sort_results();
		}

		if(!empty($this->output_depth)) {
			// let's filter categories by output depth.
			$this->_remove_by_output_depth();
		}

		// process offset parameter
		if(!empty($this->remove_from_begin)) {
			$this->categories = array_splice($this->categories, $this->remove_from_begin);
		}
		if(!empty($this->remove_from_end)) {
			array_splice($this->categories, -$this->remove_from_end);
		}

		return $this->_generate_output();
	} // end function _get_by_cat

	private function _get_by_entry_id() {
		$cat_array = array();
		$multiple_entry_ids = (strpos($this->entry_ids,',') === false) ? false : true;

		// first grab the category group information we need. Makes $this->group_ids available for use
		if(!$this->_get_cat_group_info(2)) {
			return $this->EE->TMPL->no_results(); // no category groups were found
		}

		if($this->custom_fields == 'yes') {
			// load typography library and grab info for custom category fields. Makes $this->custom_fields_arr available for use
			$this->_get_custom_fields();
		}

		// grab all categories from the category groups we've collected

		if($this->sql_type == 1) { // most advanced query
			$status = $this->EE->db->escape_str($this->status);
			$status = str_replace('|', "','", $status);
			$timestamp = time();
			$sql_extra = '';
			if($status != '') {
				$sql_extra .= ' AND ct.status IN (\''.$status.'\')';
			}
			if($this->count_future_entries == 'no') {
				$sql_extra .= ' AND ct.entry_date < '.$timestamp;
			}
			if($this->count_expired_entries == 'no') {
				$sql_extra .= ' AND (ct.expiration_date = 0 OR ct.expiration_date > '.$timestamp.')';
			}

			if($this->custom_fields == 'no') {
				$sql =	'SELECT site_id, c.cat_id, group_id, parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, ';
				$sql .=	($multiple_entry_ids) ? 'group_concat(cp.entry_id separator ",") AS entry_id, ' : 'cp.entry_id, ';
				$sql .=	'(' .
						'SELECT COUNT(cp2.entry_id) FROM exp_channel_titles ct, exp_category_posts cp2 ' .
						'WHERE cp2.cat_id=c.cat_id AND cp2.entry_id=ct.entry_id AND ct.site_id IN ('.$this->EE->db->escape_str($this->site_ids).')';
				$sql .=	$sql_extra;
				$sql .=	') AS entry_count ' .
						'FROM exp_categories c ' .
						'LEFT JOIN exp_category_posts cp ON c.cat_id=cp.cat_id AND cp.entry_id IN ('.$this->EE->db->escape_str($this->entry_ids).') OR cp.entry_id IS NULL ' .
						'WHERE site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ';
			}
			else {
				$sql =	'SELECT parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, cfd.*, ';
				$sql .=	($multiple_entry_ids) ? 'group_concat(cp.entry_id separator ",") AS entry_id, ' : 'cp.entry_id, ';
				$sql .=	'(' .
						'SELECT COUNT(cp2.entry_id) FROM exp_channel_titles ct, exp_category_posts cp2 ' .
						'WHERE cp2.cat_id=c.cat_id AND cp2.entry_id=ct.entry_id AND ct.site_id IN ('.$this->EE->db->escape_str($this->site_ids).')';
				$sql .=	$sql_extra;
				$sql .=	') AS entry_count ' .
						'FROM exp_categories c ' .
						'LEFT JOIN exp_category_posts cp ON c.cat_id=cp.cat_id AND cp.entry_id IN ('.$this->EE->db->escape_str($this->entry_ids).') OR cp.entry_id IS NULL ' .
						'LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ';
			}
		}
		elseif($this->sql_type == 2) { // simpler query
			if($this->custom_fields == 'no') {
				$sql =	'SELECT site_id, c.cat_id, group_id, parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, ';
				$sql .=	($multiple_entry_ids) ? 'group_concat(cp.entry_id separator ",") AS entry_id, ' : 'cp.entry_id, ';
				$sql .=	'(SELECT COUNT(cp2.entry_id) FROM exp_category_posts cp2 WHERE cp2.cat_id=c.cat_id) AS entry_count ' .
						'FROM exp_categories c ' .
						'LEFT JOIN exp_category_posts cp ON c.cat_id=cp.cat_id AND cp.entry_id IN ('.$this->EE->db->escape_str($this->entry_ids).') OR cp.entry_id IS NULL ' .
						'WHERE site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ';
			}
			else {
				$sql =	'SELECT parent_id, cat_name, cat_url_title, cat_description, cat_image, cat_order, cfd.*, ';
				$sql .=	($multiple_entry_ids) ? 'group_concat(cp.entry_id separator ",") AS entry_id, ' : 'cp.entry_id, ';
				$sql .=	'(SELECT COUNT(cp2.entry_id) FROM exp_category_posts cp2 WHERE cp2.cat_id=c.cat_id) AS entry_count ' .
						'FROM exp_categories c ' .
						'LEFT JOIN exp_category_posts cp ON c.cat_id=cp.cat_id AND cp.entry_id IN ('.$this->EE->db->escape_str($this->entry_ids).') OR cp.entry_id IS NULL ' .
						'LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ';
			}
		}
		else { // simplest query, no need to count entries
			if($this->custom_fields == 'no') {
				$sql =	'SELECT c.site_id, c.cat_id, c.group_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.cat_order, ';
				$sql .=	($multiple_entry_ids) ? 'group_concat(cp.entry_id separator ",") AS entry_id ' : 'cp.entry_id ';
				$sql .=	'FROM exp_categories c LEFT JOIN exp_category_posts cp ON c.cat_id=cp.cat_id AND cp.entry_id IN ('.$this->EE->db->escape_str($this->entry_ids).') OR cp.entry_id IS NULL ' .
						'WHERE c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ';
			}
			else {
				$sql =	'SELECT c.parent_id, c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.cat_order, cfd.*, ';
				$sql .=	($multiple_entry_ids) ? 'group_concat(cp.entry_id separator ",") AS entry_id ' : 'cp.entry_id ';
				$sql .=	'FROM exp_categories c LEFT JOIN exp_category_posts cp ON c.cat_id=cp.cat_id AND cp.entry_id IN ('.$this->EE->db->escape_str($this->entry_ids).') OR cp.entry_id IS NULL ' .
						'LEFT JOIN exp_category_field_data cfd ON cfd.cat_id=c.cat_id ' .
						'WHERE c.site_id=cfd.site_id AND c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND c.group_id IN ('.$this->EE->db->escape_str($this->group_ids).') ';
			}
		}
		$sql .=	($multiple_entry_ids) ? 'GROUP BY c.cat_id ORDER BY site_id, group_id, parent_id, cat_order' : 'ORDER BY site_id, group_id, parent_id, cat_order';
		if(!$this->_get_categories($sql)) {
			return $this->EE->TMPL->no_results(); // no categories were found
		}
		// $this->categories is now available and in the correct order.

		// clean up array
		$this->categories = array_values($this->categories);

		if($this->last_only == 'yes') {
			// remove all but last child categories
			$this->_remove_not_last_child_categories();
		}

		if(!empty($this->depth) || !empty($this->min_depth) || !empty($this->max_depth)) {
			// we may need to remove categories based on the depth params
			$this->_remove_categories_by_depth();
		}

		if($this->show_full_trail == 'no') {
			// remove all categories that aren't associated with this entry_id
			foreach($this->categories as $key => $val) {
				if(empty($val['entry_id'])) {
					unset($this->categories[$key]);
				}
			}
		}
		else {
			$prev_depth = $prev_parent_id = null;
			foreach(array_reverse($this->categories, true) as $key => $val) {
				$delete = true;
				if(!empty($val['entry_id']) || ($val['cat_id'] == $prev_parent_id && $val['depth'] != $prev_depth)) {
					$delete = false;
					$prev_parent_id = $val['parent_id'];
					$prev_depth = $val['depth'];
				}
				if($delete) {
					unset($this->categories[$key]);
				}
			}
		}

		// we may have removed something in the array, clean up array
		$this->categories = array_values($this->categories);

		if($this->show_empty == 'no') {
			// remove categories that have no entries
			$this->_remove_empty_categories();
		}

		if(!empty($this->excl_cat_id)) {
			// remove categories and potential subcategories that have been provided with the excl_cat_id parameter
			$remove_children = ($this->excl_cat_id_children == 'yes') ? true : false;
			$this->_exclude_categories($remove_children);
		}

		if(!empty($this->show)) {
			// remove categories not provided with the show parameter
			$this->_remove_not_in_show();
		}

		if(!empty($this->orderby)) {
			$this->_sort_results();
		}

		if(!empty($this->output_depth)) {
			// let's filter categories by output depth.
			$this->_remove_by_output_depth();
		}

		// process offset parameter
		if(!empty($this->remove_from_begin)) {
			$this->categories = array_splice($this->categories, $this->remove_from_begin);
		}
		if(!empty($this->remove_from_end)) {
			array_splice($this->categories, -$this->remove_from_end);
		}

		return $this->_generate_output();
	} // end function _get_by_entry_id

	private function _generate_output() {
		if($this->style == 'linear') {
			$linear_parse_vars_arr = array();
		}
		else {
			$gw_output = $gw_output_per_group = '';
		}
		$output_path_arr = $depthx_start_open = array();

		// create switch_arr which holds our switch parameter values. We need to do this manually since we're not using parse_variables.
		if(strpos($this->switch,'|') !== false) {
			$switch_arr = explode('|',$this->switch);
			$switch_arr = array_filter($switch_arr);
		}
		else {
			$switch_arr = array();
			if(trim($this->switch) != '') {
				$switch_arr[] = $this->switch;
			}
		}
		$switch_count = count($switch_arr);

		$prev_group_id = $prev_depth = 0;
		for($i=$this->min_depth_in_output;$i<=$this->max_depth_in_output;$i++) {
			${'depth'.$i.'_start_count'} = ${'depth'.$i.'_end_count'} = 0;
		}

		$results_total = count($this->categories);

		$output_limit = (!empty($this->limit) && ($this->limit < $results_total)) ? $this->limit : $results_total;
		if($output_limit == 0) {
			return $this->EE->TMPL->no_results();
		}

		if(version_compare(APP_VER, '2.4', '>=')) { // we need to load the file_field library in v 2.4.0 and up to parse filedir_x variables
			$this->EE->load->library('file_field');
		}

		// depending on the given parameters, we may need to create a simple UL/OL list, with just 1 depth.
		$current_ul_depth = 0;
		if($this->style == 'simple') {
			$simple_ul_list = true; // all groups should be simple
		}
		elseif($this->style == 'nested') {
			$simple_ul_list = false;

			// first, check for the obvious
			if($this->last_only == 'yes') {
				$simple_ul_list = true; // all groups should be simple
			}
			if(!empty($this->orderby)) {
				$simple_ul_list = true; // all groups should be simple
			}
			// then, if needed, use the _check_if_simple_list function
			if(!$simple_ul_list) {
				$simple_ul_list_arr = $this->_check_if_simple_list();
			}
		}

		if($this->style != 'linear') {
			// let's see if the {group_heading}..{/group_heading} tag pair is used.
			// if so, $matches_arr[1][0] = code between tag pairs. $matches_arr[0][0] = {group_heading}..{/group_heading}
			$pattern = LD.'group_heading'.RD.'(.*?)'.LD.'\/group_heading'.RD;
			$group_heading_count = preg_match_all('/'.$pattern.'/msi', $this->gw_tagdata, $matches_arr);
		}

		for($gw_i=0;$gw_i<$output_limit;$gw_i++) {
			$group_start = ($this->categories[$gw_i]['cat_group_id'] != $prev_group_id) ? true : false; // do we start a new category group?
			$last_cat_to_display = ($gw_i+1 == $output_limit) ? true : false; // is this the very last category we're going to display?
			if(!$last_cat_to_display) {
				$last_cat_in_group = ($this->categories[$gw_i]['cat_group_id'] != $this->categories[$gw_i+1]['cat_group_id']) ? true : false; // is this the last category in this group?
			}
			else {
				$last_cat_in_group = true;
			}

			if(!$last_cat_in_group) {
				$has_children_in_output = ($this->categories[$gw_i+1]['parent_id'] == $this->categories[$gw_i]['cat_id']) ? true : false;
			}
			else {
				$has_children_in_output = false;
			}

			if($group_start) {
				$output_path_arr = array();
				$cat_count_in_group = 1;
				if(isset($simple_ul_list_arr)) { // check if current group should be simple or not
					$simple_ul_list = ($simple_ul_list_arr[$this->categories[$gw_i]['cat_group_id']]) ? true : false;
				}
				if($this->style != 'linear') { // start new category group ul/ol
					$gw_output_per_group .= '<'.$this->list_type;
					if(!empty($this->id)) {
						$gw_output_per_group .= ' id="'.$this->id.'"';
					}
					if(!empty($this->class)) {
						$gw_output_per_group .= ' class="'.$this->class.'"';
					}
					$gw_output_per_group .= '>'."\n";
					$current_ul_depth = 1;
				}
			}
			if($this->style != 'linear') {
				$tagdata = $this->gw_tagdata;
				if($group_heading_count > 0) {
					$tagdata = str_replace($matches_arr[0][0],'',$tagdata); // remove {group_heading}..{/group_heading} tag pair
				}
				$gw_output_per_group .= '<li>';
			}

			// start: create output_path
			if($current_ul_depth <= 1) {
				$output_path_arr = array();
			}
			else {
				array_splice($output_path_arr, $current_ul_depth-1);
			}
			$output_path_arr[] = $this->categories[$gw_i]['cat_url_title'];
			// end: create output_path

			$cat_image = $this->categories[$gw_i]['cat_image'];
			if(version_compare(APP_VER, '2.4', '>=')) {
				$cat_image = ($cat_image == '0' || $cat_image == '') ? '' : $this->EE->file_field->parse_string($cat_image);
			}

			$var_values_arr = array(
								$this->var_prefix.'site_id' => $this->categories[$gw_i]['site_id'],
								$this->var_prefix.'cat_count' => $gw_i+1,
								$this->var_prefix.'cat_id' => $this->categories[$gw_i]['cat_id'],
								$this->var_prefix.'cat_name' => $this->categories[$gw_i]['cat_name'],
								$this->var_prefix.'cat_url_title' => $this->categories[$gw_i]['cat_url_title'],
								$this->var_prefix.'cat_description' => $this->categories[$gw_i]['cat_description'],
								$this->var_prefix.'cat_image' => $cat_image,
								$this->var_prefix.'cat_order' => $this->categories[$gw_i]['cat_order'],
								$this->var_prefix.'parent_id' => $this->categories[$gw_i]['parent_id'],
								$this->var_prefix.'parent_url_title' => $this->categories[$gw_i]['parent_url_title'],
								$this->var_prefix.'parent_name' => $this->categories[$gw_i]['parent_name'],
								$this->var_prefix.'cat_group_id' => $this->categories[$gw_i]['cat_group_id'],
								$this->var_prefix.'cat_group_name' => $this->categories[$gw_i]['cat_group_name'],
								$this->var_prefix.'depth' => $this->categories[$gw_i]['depth'],
								$this->var_prefix.'results' => $output_limit,
								$this->var_prefix.'results_total' => $results_total,
								$this->var_prefix.'group_start' => $group_start,
								$this->var_prefix.'has_children' => $this->categories[$gw_i]['has_children'],
								$this->var_prefix.'entry_count' => $this->categories[$gw_i]['entry_count'],
								$this->var_prefix.'li_depth' => $current_ul_depth,
								$this->var_prefix.'complete_path' => implode('/',$this->categories[$gw_i]['complete_path']),
								$this->var_prefix.'group_end' => $last_cat_in_group,
								$this->var_prefix.'cat_count_in_group' => $cat_count_in_group,
								$this->var_prefix.'output_path' => implode('/',$output_path_arr),
								$this->var_prefix.'has_children_in_output' => $has_children_in_output,
								$this->var_prefix.'switch' => ($switch_count > 0) ? $switch_arr[($gw_i % $switch_count)] : ''
								);

			// start: add depthX_start / depthX_end / depthX_start_count / depthX_end_count variable values
			for($i=1;$i<=$this->max_depth_in_output;$i++) {
				${'depth'.$i.'_start'} = ${'depth'.$i.'_end'} = false;
				if($prev_depth != $i && $this->categories[$gw_i]['depth'] == $i) {
					${'depth'.$i.'_start'} = true;
					${'depth'.$i.'_start_count'}++;
					$depthx_start_open[$i] = true;
				}
				if($last_cat_in_group) { // end current category depth and all lower ones
					if(isset($depthx_start_open[$i]) && $depthx_start_open[$i]) {
						${'depth'.$i.'_end'} = true;
						${'depth'.$i.'_end_count'}++;
					}
				}
				elseif($i <= $this->categories[$gw_i]['depth'] && $this->categories[$gw_i+1]['depth'] <= $i && $this->categories[$gw_i]['depth'] != $this->categories[$gw_i+1]['depth']) {
					${'depth'.$i.'_end'} = true;
					${'depth'.$i.'_end_count'}++;
					$depthx_start_open[$i] = false;
				}
				$var_values_arr[$this->var_prefix.'depth'.$i.'_start'] = ${'depth'.$i.'_start'};
				$var_values_arr[$this->var_prefix.'depth'.$i.'_end'] = ${'depth'.$i.'_end'};
				$var_values_arr[$this->var_prefix.'depth'.$i.'_start_count'] = ${'depth'.$i.'_start_count'};
				$var_values_arr[$this->var_prefix.'depth'.$i.'_end_count'] = ${'depth'.$i.'_end_count'};
			}
			// end: add depthX_start / depthX_end / depthX_start_count / depthX_end_count variable values

			// start: add custom category fields
			if($this->custom_fields == 'yes' && !empty($this->custom_fields_arr)) {
				foreach($this->custom_fields_arr as $cf_group_id => $cf_group_arr) {
					foreach($this->custom_fields_arr[$cf_group_id] as $cf_arr_key => $cf_field_array) {
						// if custom category fields are available for this category group, add them. Make custom category fields for other groups empty.
						$var_values_arr[$this->var_prefix.$cf_field_array['field_name']] = ($this->categories[$gw_i]['cat_group_id'] == $cf_group_id) ? $this->categories[$gw_i][$cf_field_array['field_name']] : '';
					}
				}
			}
			// end: add custom category fields

			if($this->style != 'linear') {
				$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $var_values_arr);
			}
			else {
				$linear_parse_vars_arr[] = $var_values_arr;
			}

			if($this->style != 'linear') {
				$gw_output_per_group .= $tagdata;
				if($simple_ul_list) {
					// we're creating a simple 1 depth ul list for this category group
					$gw_output_per_group .= '</li>'."\n";
					if($last_cat_to_display || $last_cat_in_group) { // if this is the very last category we're displaying, or the last in this group
						$gw_output_per_group .= '</'.$this->list_type.'>'."\n";
					}
				}
				else {
					if(!$last_cat_to_display && !$last_cat_in_group) { // if this is not the very last category we're displaying and not the last category in this group
						if($this->categories[$gw_i+1]['depth'] > $this->categories[$gw_i]['depth']) { // the next category has a higher depth --> create new ul/ol
							$gw_output_per_group .= '<'.$this->list_type.'>'."\n";
							$current_ul_depth++;
						}
						elseif($this->categories[$gw_i+1]['depth'] == $this->categories[$gw_i]['depth']) { // the next category has the same depth
							$gw_output_per_group .= '</li>'."\n";
						}
						else { // the next category has a lower depth --> close open li's and ul's
							$diff = $this->categories[$gw_i]['depth'] - $this->categories[$gw_i+1]['depth'];
							for($j=0;$j<$diff;$j++) {
								$gw_output_per_group .= '</li>'."\n".'</'.$this->list_type.'>'."\n";
								$current_ul_depth--;
							}
							$gw_output_per_group .= '</li>'."\n";
						}
					}
					else { // if this is the very last category or the last category in this group --> close open li's and ul's
						for($j=0;$j<$current_ul_depth;$j++) {
							$gw_output_per_group .= '</li>'."\n".'</'.$this->list_type.'>'."\n";
						}
					}
				}
				if($last_cat_in_group) {
					if($group_heading_count > 0) { // {group_heading}..{/group_heading} tag pair was found
						// parse group variables in between {group_heading}..{/group_heading} tag pair and add it to the top of the output for this category group
						$group_heading = str_replace(array('{'.$this->var_prefix.'cat_group_id}','{'.$this->var_prefix.'cat_group_name}','{'.$this->var_prefix.'cat_count_in_group}'),array($this->categories[$gw_i]['cat_group_id'],$this->categories[$gw_i]['cat_group_name'],$cat_count_in_group),$matches_arr[1][0]);
						$gw_output_per_group = $group_heading."\n".$gw_output_per_group;
					}
					// add output for this category group to the final output
					$gw_output .= $gw_output_per_group;
					$gw_output_per_group = '';
				}
			}

			$cat_count_in_group++;
			$prev_group_id = $this->categories[$gw_i]['cat_group_id'];
			$prev_depth = $this->categories[$gw_i]['depth'];
		}

		return ($this->style != 'linear') ? $gw_output : $this->EE->TMPL->parse_variables(rtrim($this->gw_tagdata), $linear_parse_vars_arr);
	} // end function _generate_output

	private function _category_subtree($cat_id, $cat_url_title, $cat_name, $cat_array, $depth) {
		// borrowed from Api_channel_categories.php

		if($depth > $this->max_depth_in_output) {
			$this->max_depth_in_output = $depth;
		}

		$depth++;

		foreach($cat_array as $key => $val) {
			if($val['parent_id'] == $cat_id) {
				$this->categories[$val['parent_id']]['has_children'] = true; // the parent category has children
				$this->categories[$key] = array_merge(array('cat_id' => $key, 'depth' => $depth, 'complete_path' => $this->categories[$cat_id]['complete_path']), $cat_array[$key]);
				$this->categories[$key]['complete_path'][$key] = $val['cat_url_title'];
				$this->categories[$key]['parent_url_title'] = $cat_url_title;
				$this->categories[$key]['parent_name'] = $cat_name;
				$this->_category_subtree($key, $this->categories[$key]['cat_url_title'], $this->categories[$key]['cat_name'], $cat_array, $depth);
			}
		}
	} // end function _category_subtree

	private function _remove_not_last_child_categories() {
		// remove all but last child categories
		foreach($this->categories as $key => $val) {
			if($val['has_children']) {
				unset($this->categories[$key]);
			}
		}
		// we may have removed something in the array, clean up array
		$this->categories = array_values($this->categories);
	} // end function _remove_not_last_child_categories

	private function _remove_categories_by_depth() {
		// remove categories based on the depth, min_depth and max_depth params
		$depth_arr = explode('|',$this->depth);
		foreach($this->categories as $key => $val) {
			$add_to_output = false;
			if(!empty($this->depth) && in_array($val['depth'],$depth_arr)) {
				$add_to_output = true;
			}
			if(!empty($this->min_depth) && $val['depth'] >= $this->min_depth) {
				$add_to_output = true;
			}
			if(!empty($this->max_depth) && $val['depth'] <= $this->max_depth) {
				$add_to_output = true;
			}
			if(!$add_to_output) {
				unset($this->categories[$key]);
			}
		}
		// we may have removed something in the array, clean up array
		$this->categories = array_values($this->categories);

		// adjust min_depth_in_output / max_depth_in_output values
		if(!empty($this->max_depth)) {
			$this->max_depth_in_output = $this->max_depth;
		}
		if(!empty($this->min_depth)) {
			$this->min_depth_in_output = $this->min_depth;
		}
		foreach($depth_arr as $key => $depth) {
			if($depth < $this->min_depth_in_output) {
				$this->min_depth_in_output = $depth;
			}
			if($depth > $this->max_depth_in_output) {
				$this->max_depth_in_output = $depth;
			}
		}
	} // end function _remove_categories_by_depth

	private function _remove_empty_categories() {
		// remove categories that have no entries
		foreach($this->categories as $key => $val) {
			if($val['entry_count'] == 0) {
				unset($this->categories[$key]);
			}
		}
		// we may have removed something in the array, clean up array
		$this->categories = array_values($this->categories);
	} // end function _remove_empty_categories

	private function _check_if_simple_list() {
		// check if a list should be simple (1 depth) or not. For example, if we only want to show categories with depth 1 and 3, we can't show a proper nested list.
		// we check this for every category group seperately.
		$current_group_id = 0;
		$avail_cat_ids = array(); // available cat_id's in the tree
		$cat_count = count($this->categories);
		for($i=0;$i<$cat_count;$i++) {
			if($current_group_id != $this->categories[$i]['cat_group_id']) { // we start a new category group
				$break = false;
				$current_group_id = $this->categories[$i]['cat_group_id'];
				$simple_ul_arr[$current_group_id] = false;
				$avail_cat_ids = array(); // let's start with an empty array
				$avail_cat_ids[] = $this->categories[$i]['parent_id']; // since this is the first category in the tree, the parent_id should also be added
				$avail_cat_ids[] = $this->categories[$i]['cat_id'];
				$root_depth = $this->categories[$i]['depth'];
			}
			elseif(!$break) {
				$avail_cat_ids[] = $this->categories[$i]['cat_id'];
				if(!in_array($this->categories[$i]['parent_id'], $avail_cat_ids) && $this->categories[$i]['depth'] != $root_depth) {
					$simple_ul_arr[$current_group_id] = $break = true; // set the ul list to simple for this category group
				}
			}
		}
		return $simple_ul_arr;
	} // end function _check_if_simple_list

	private function _get_cat_group_info($type=null) {
		// grab the category group information we need.

		$group_ids = str_replace('|', ',', $this->group_id);

		switch($type) {
			case 1: // _get_by_cat (cat_id param)
				$sql = 'SELECT cg.group_id, group_name, field_html_formatting FROM exp_category_groups cg, exp_categories c WHERE c.group_id=cg.group_id AND c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND c.cat_id IN ('.$this->EE->db->escape_str($this->cat_id).')';
				break;
			case 2: // _get_by_entry_id
				if(!empty($this->group_id)) {
					$sql = 'SELECT cg.group_id, group_name, field_html_formatting FROM exp_category_groups cg WHERE site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND group_id IN ('.$this->EE->db->escape_str($group_ids).')';
				}
				else {
					$sql = 'SELECT DISTINCT(cg.group_id), group_name, field_html_formatting FROM exp_category_groups cg, exp_category_posts cp, exp_categories c WHERE c.site_id=cg.site_id AND cp.cat_id=c.cat_id AND cg.group_id=c.group_id AND cg.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND cp.entry_id IN ('.$this->EE->db->escape_str($this->entry_ids).')';
				}
				break;
			case 3: // _get_all
				$sql = 'SELECT group_id, group_name, field_html_formatting FROM exp_category_groups WHERE site_id IN ('.$this->EE->db->escape_str($this->site_ids).')';
				break;
			case 4: // _get_by_cat (cat_url_title param) --> fetch cat_id as well for future use
				$sql =	'SELECT cg.group_id, group_name, field_html_formatting, cat_id FROM exp_category_groups cg, exp_categories c WHERE c.group_id=cg.group_id AND c.site_id IN ('.$this->EE->db->escape_str($this->site_ids).') ';
				if(!empty($this->group_id)) { // limit the search for the correct cat_id for this cat_url_title by using the provided group_id(s)
					$sql .=	'AND cg.group_id IN ('.$this->EE->db->escape_str($group_ids).') ';
				}
				$sql .=	"AND c.cat_url_title='".$this->EE->db->escape_str($this->cat_url_title)."'";
				break;
			default: // _get_by_group_id
				$sql = 'SELECT cg.group_id, group_name, field_html_formatting FROM exp_category_groups cg WHERE site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND group_id IN ('.$this->EE->db->escape_str($group_ids).')';
				break;

		}
		if(!empty($group_ids)) {
			$sql .= ' ORDER BY FIELD(cg.group_id, '.$this->EE->db->escape_str($group_ids).')';
		}
		if($type == 4) { // multiple category groups can have a category with the same cat_url_title, we only need 1 result
			$sql .= ' LIMIT 1';
		}
		$gwc_result = $this->EE->db->query($sql);
		if($gwc_result->num_rows() == 0) {
			return false;
		}
		foreach($gwc_result->result_array() as $row) {
			$this->group_id_arr[$row['group_id']] = array('group_name' => $row['group_name'], 'field_html_formatting' => $row['field_html_formatting']);
			if($type == 4) {
				$this->cat_id = $row['cat_id']; // we now have the cat_id that we can work with, teehee!
			}
		}

		$this->group_ids = implode(',',array_keys($this->group_id_arr));
		return true;
	}

	private function _get_custom_fields() {
		// load typography library and grab custom category fields

		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->convert_curly = false;

		// grab custom field names and add them to array
		$sql = 'SELECT field_id, field_name, group_id FROM exp_category_fields WHERE site_id IN ('.$this->EE->db->escape_str($this->site_ids).') AND group_id IN('.$this->EE->db->escape_str($this->group_ids).')';
		$gwc_result = $this->EE->db->query($sql);
		if($gwc_result->num_rows() != 0) {
			foreach($gwc_result->result_array() as $row) {
				$this->custom_fields_arr[$row['group_id']][] = array('field_id' => $row['field_id'], 'field_name' => $row['field_name']);
			}
		}
	}

	private function _get_categories($sql) {
		// grab all categories and add them to $this->categories
		$gwc_result = $this->EE->db->query($sql);
		if($gwc_result->num_rows() == 0) {
			return false;
		}
		foreach($gwc_result->result_array() as $row) {
			$entry_id = (array_key_exists('entry_id',$row)) ? $row['entry_id'] : '';
			$entry_count = (array_key_exists('entry_count',$row)) ? $row['entry_count'] : '';
			$cat_array[$row['cat_id']] = array(
											'site_id' => $row['site_id'],
											'cat_name' => $row['cat_name'],
											'cat_url_title' => $row['cat_url_title'],
											'cat_description' => $row['cat_description'],
											'cat_image' => $row['cat_image'],
											'cat_order' => $row['cat_order'],
											'parent_id' => $row['parent_id'],
											'parent_url_title' => '',
											'parent_name' => '',
											'cat_group_id' => $row['group_id'],
											'cat_group_name' => $this->group_id_arr[$row['group_id']]['group_name'],
											'entry_id' => $entry_id,
											'entry_count' => $entry_count,
											'has_children' => false
											);
			if($this->custom_fields == 'yes' && array_key_exists($row['group_id'],$this->custom_fields_arr)) { // if this category group has custom category fields)
				// add custom field data
				foreach($this->custom_fields_arr[$row['group_id']] as $cf_arr_key => $cf_field_array) {
					$custom_field_formatted = $this->EE->typography->parse_type($row['field_id_'.$cf_field_array['field_id']], array('text_format' => $row['field_ft_'.$cf_field_array['field_id']], 'html_format' => $this->group_id_arr[$row['group_id']]['field_html_formatting'], 'auto_links' => 'n', 'allow_img_url' => 'y'));
					$cat_array[$row['cat_id']][$cf_field_array['field_name']] = $custom_field_formatted;
				}
			}
		}
		// the $cat_array now has all categories we need. It's not in the correct order yet, so let's create the correct order.
		// we're also going to add the depth and complete_path values and set the parent_url_title and parent_name values in the _category_subtree function.
		foreach($cat_array as $key => $val)	{
			if($val['parent_id'] == 0) {
				$depth = 1;
				$this->categories[$key] = array_merge(array('cat_id' => $key, 'depth' => $depth, 'complete_path' => array($key => $val['cat_url_title'])), $cat_array[$key]);
				$this->_category_subtree($key, $val['cat_url_title'], $val['cat_name'], $cat_array, $depth);
			}
		}
		// $this->categories is now in the correct order.
		return true;
	}

	private function _exclude_categories($remove_children=true) {
		// - remove categories and potential subcategories provided with the excl_cat_id parameter
		$excl_arr = explode('|',$this->excl_cat_id);
		foreach($this->categories as $key => $category) {
			if($remove_children) {
				if(in_array($category['parent_id'],$excl_arr)) {
					$excl_arr[] = $category['cat_id'];
				}
			}
			if(in_array($category['cat_id'],$excl_arr)) {
				unset($this->categories[$key]);
			}
		}
		// we may have removed something in the array, clean up array
		$this->categories = array_values($this->categories);
	} // end function _exclude_categories

	private function _remove_not_in_show() {
		// remove categories not provided with the show parameter
		$show_arr = explode('|',$this->show);
		foreach($this->categories as $key => $category) {
			if(!in_array($category['cat_id'],$show_arr)) {
				unset($this->categories[$key]);
			}
		}
		// we may have removed something in the array, clean up array
		$this->categories = array_values($this->categories);
	}

	private function _remove_by_output_depth() {
		// a category with depth 3 for example may become a root category in the final output ( --> output depth: 1).
		$prev_cat_group = 0;
		foreach($this->categories as $key => $category) {
			if($category['cat_group_id'] != $prev_cat_group) { // new category group
				$lowest_depth[$category['cat_group_id']] = $category['depth'];
			}
			if($category['depth'] < $lowest_depth[$category['cat_group_id']]) {
				$lowest_depth[$category['cat_group_id']] = $category['depth'];
			}
			$prev_cat_group = $category['cat_group_id'];
		}
		// we now have the lowest depth per category group. A group's lowest depth will be assigned an "output depth" of 1.
		// remove all categories with an output depth that is not provided with the output_depth parameter.
		$output_depth_arr = explode('|',$this->output_depth);
		foreach($this->categories as $key => $category) {
			$output_depth = ($category['depth'] - $lowest_depth[$category['cat_group_id']]) + 1;
			if(!in_array($output_depth,$output_depth_arr)) {
				unset($this->categories[$key]);
			}
		}
		// we may have removed something in the array, clean up array
		$this->categories = array_values($this->categories);
	} // end function _remove_by_output_depth

	private function _sort_results() {
		$orderby_arr = explode('|',$this->orderby);
		$sort_arr = explode('|',$this->sort);
		if(count($orderby_arr) == 1) {
			$col_name = $orderby_arr[0];
			if($col_name == 'random') { // no need to do this for multi sort (we only allow random to be used when 1 value is given for the orderby parameter)
				shuffle($this->categories);
			}
			else {
				if($this->sort == 'desc') {
					usort($this->categories, create_function('$b,$a','return strnatcasecmp($a["'.$col_name.'"],$b["'.$col_name.'"]);')); // sort by entry_count, highest numbers above
				}
				else {
					usort($this->categories, create_function('$a,$b','return strnatcasecmp($a["'.$col_name.'"],$b["'.$col_name.'"]);')); // sort by entry_count, lowest numbers above
				}
			}
		}
		else { // multi sort
			$data = array();
			$first_loop = true;
			$orderby_count = count($orderby_arr);
			$sort_flag = array();
			foreach($this->categories as $key => $row) {
				foreach($orderby_arr as $key2 => $row2) {
					$data[$row2][$key] = $row[$row2];
					if($first_loop) {
						$sort_flag[$key2] = ($sort_arr[$key2] == 'asc') ? SORT_ASC : SORT_DESC;
					}
				}
				$first_loop = false;
			}
			if($orderby_count == 2) {
				array_multisort($data[$orderby_arr[0]], $sort_flag[0], $data[$orderby_arr[1]], $sort_flag[1], $this->categories);
			}
			elseif($orderby_count == 3) {
				array_multisort($data[$orderby_arr[0]], $sort_flag[0], $data[$orderby_arr[1]], $sort_flag[1], $data[$orderby_arr[2]], $sort_flag[2], $this->categories);
			}
		}
	} // end function _sort_results

	// ----------------------------------------
	// Plugin Usage
	// ----------------------------------------
	// This function describes how the plugin is used.
	public static function usage() {
		ob_start();
?>
The tag in its most simple form:
{exp:gwcode_categories}{/exp:gwcode_categories}
..will show a nested list of all category groups (wrapped in h2 tags) and its categories.

To get the list of categories you need, you can use parameters. The ones with a dash can only be used in combination with the parameter above it without the dash.

PARAMETERS:
site_id
channel
group_id
entry_id
	- show_full_trail
limit
last_only
style
list_type
backspace
id
class
depth
min_depth
max_depth
entry_count
status
count_future_entries
count_expired_entries
show_empty
cat_id / cat_url_title
	- show_trail
	- incl_self
custom_fields
excl_cat_id
	- excl_cat_id_children
output_depth
orderby
sort
offset
excl_group_id
variable_prefix
show
switch

The following variables can be used to control what the ouput should look like.

VARIABLES:
{site_id}
{cat_count}
{group_heading}..{/group_heading} (not available when using style="linear")
{cat_group_id}
{cat_group_name}
{cat_id}
{cat_name}
{cat_url_title}
{cat_description}
{cat_image}
{cat_order}
{parent_id}
{parent_url_title}
{parent_name}
{depth}
{results}
{results_total}
{group_start}
{has_children}
{entry_count}
{li_depth}
{complete_path}
{group_end}
{cat_count_in_group}
{output_path}
{has_children_in_output}
{depthX_start} (X being a number)
{depthX_end} (X being a number)
{depthX_start_count} (X being a number)
{depthX_end_count} (X being a number)
{switch}

You can use {if no_results}No results{/if} as well.
Custom category fields you may have created are available as variables when the custom_fields parameter has been set to "yes".

Example - Showing categories with depth 1 or 2 for one or more channels:
{exp:gwcode_categories channel="example|example2" depth="1|2"}
	Category name: {cat_name}. Depth: {depth}
{/exp:gwcode_categories}

For more examples, visit http://gwcode.com/add-ons/gwcode-categories/examples
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
} // END CLASS
?>
