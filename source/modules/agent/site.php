<?php
/**
 * 代理商模块微站定义
 *
 * @author WeEngine Team
 * @url
 */
defined('IN_IA') or exit('Access Denied');
session_start();
class AgentModuleSite extends WeModuleSite {

	public function doWebUser() {
		global $_W, $_GPC;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;

            $where = '';
            if (isset($_GPC['status']) && $_GPC['status'] !== '') {
                $where .= " AND a.status = '".intval($_GPC['status'])."'";
            }
            if (!empty($_GPC['username'])) {
                $where .= " AND a.username LIKE '%{$_GPC['username']}%'";
            }

            if (!empty($_GPC['group'])) {
                $where .= " AND a.groupid = '{$_GPC['group']}'";
            }
            $where .= "AND b.agent_uid = {$_W['uid']}";
            $sql ='SELECT * FROM ' . tablename('members') . ' a LEFT JOIN ' . tablename('agent_user') . ' b ON a.uid=b.uid WHERE 1 ' .$where . " LIMIT " . ($pindex - 1) * $psize .',' .$psize;
            $list = pdo_fetchall($sql);
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('members') . ' a LEFT JOIN ' . tablename('agent_user') . ' b ON a.uid=b.uid WHERE 1 ' . $where);
            $pager = pagination($total, $pindex, $psize);

            $founders = explode(',', $_W['config']['setting']['founder']);
            foreach($members as &$m) {
                $m['founder'] = in_array($m['uid'], $founders);
            }

            $usergroups = pdo_fetchall("SELECT id, name FROM ".tablename('members_group'), array(), 'id');
		} elseif ($operation == 'post') {
			$id = intval($_GPC['id']);
			$agent_uid = intval($_W['uid']);
            $extendfields = pdo_fetchall("SELECT field, title, description, required FROM ".tablename('profile_fields')." WHERE available = '1' AND showinregister = '1'");
			if (!empty($id)) {
				$member = pdo_fetch("SELECT * FROM ".tablename('members')." WHERE uid = :uid" , array(':uid' => $id));
				if (empty($member)) {
					message('抱歉，用户不存在或是已经删除！', '', 'error');
				}
			}
			if (checksubmit('profile_submit')) {
                $nMember = array();
                $nMember['uid'] = $id;
                $nMember['password'] = $_GPC['password'];
                $nMember['groupid'] = intval($_GPC['groupid']);
                if(!empty($nMember['password']) && istrlen($nMember['password']) < 8) {
                    message('必须输入密码，且密码长度不得低于8位。');
                }
                $nMember['lastip'] = $_GPC['lastip'];
                $nMember['lastvisit'] = strtotime($_GPC['lastvisit']);
                $nMember['remark'] = $_GPC['remark'];

                if ($member['groupid'] != $nMember['groupid']) {
                    # 扣钱
                    $balance = pdo_fetch("SELECT * FROM ".tablename('agent')." WHERE uid = :uid" , array(':uid' => $agent_uid));
                    $reduce_money = pdo_fetch("SELECT * FROM ".tablename('agent_payment')." WHERE agent_uid = :uid AND group_id = :group_id" , array(':uid' => $agent_uid, ':group_id' => $nMember['groupid']));
                    if(empty($reduce_money) || $balance['balance'] < $reduce_money['amount_needs']) {
                        message('余额不足，不能修改分组。');
                    }

                    $new_balance = $balance['balance'] - $reduce_money['amount_needs'];
                    pdo_update('agent', array('balance' => $new_balance), array('uid' => $agent_uid));
                    $log = array();
                    $log['agent_uid'] = $agent_uid;
                    $log['datetime'] = time();
                    $log['log'] = "修改用户" .$id. "分组:" .$member['groupid']. " --> " .$nMember['groupid'];
                    pdo_insert('agent_log', $log);
                }

                member_update($nMember);

                if (!empty($extendfields)) {
                    foreach ($extendfields as $row) {
                        if($row['field'] != 'profile') $profile[$row['field']] = $_GPC[$row['field']];
                    }
                    if (!empty($profile)) {
                        $exists = pdo_fetchcolumn("SELECT uid FROM ".tablename('members_profile')." WHERE uid = :uid", array(':uid' => $id));
                        if (!empty($exists)) {
                            pdo_update('members_profile', $profile, array('uid' => $id));
                        } else {
                            $profile['uid'] = $id;
                            pdo_insert('members_profile', $profile);
                        }
                    }
                }
				message('用户信息更新成功！', create_url('site/module/user', array('name' => 'agent', 'op' => 'display')), 'success');
			}
            if (!empty($extendfields)) {
                foreach ($extendfields as $row) {
                    $fields[] = $row['field'];
                }
                $member['profile'] = pdo_fetch("SELECT `".implode("`,`", $fields)."` FROM ".tablename('members_profile')." WHERE uid = :uid", array(':uid' => $id));
            }
            $groups = pdo_fetchall("SELECT id, name FROM ".tablename('members_group')." ORDER BY id ASC");
		}

		include $this->template('user');
	}
}
