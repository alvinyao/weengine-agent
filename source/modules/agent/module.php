<?php
/**
 * 代理商模块定义
 *
 * @author alvin
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class AgentModule extends WeModule {

	public function fieldsFormDisplay($rid = 0) {
		global $_W;
		$setting = $_W['account']['modules'][$this->_saveing_params['mid']]['config'];
		include $this->template('rule');
	}

	public function fieldsFormSubmit($rid = 0) {
		global $_GPC, $_W;
		if (!empty($_GPC['title'])) {
			$data = array(
				'title' => $_GPC['title'],
				'description' => $_GPC['description'],
				'picurl' => $_GPC['thumb-old'],
				'url' => create_url('mobile/module/list', array('name' => 'shopping', 'weid' => $_W['weid'])),
			);
			if (!empty($_GPC['thumb'])) {
				$data['picurl'] = $_GPC['thumb'];
				file_delete($_GPC['thumb-old']);
			}
			$this->saveSettings($data);
		}
		return true;
	}
	
	public function settingsDisplay($settings) {
		global $_GPC, $_W;
		if(checksubmit()) {
			$cfg = array(
				'noticeemail' => $_GPC['noticeemail'],
			);
			if($this->saveSettings($cfg)) {
				message('保存成功', 'refresh');
			}
		}
		include $this->template('setting');
	}

}
