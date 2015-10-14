<?php
/**
*
*/
class OfficeMenuController extends \BaseController
{
    
    public function listMenu()
    {
        try {
            $menu_tree = SysMenu::getTreeByAdmin(-1);
            $re = Tools::reTrue('获取菜单成功', $menu_tree);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取菜单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postMenu()
    {
        $name = Input::get('name', '');
        $desc = Input::get('desc', '');
        $parent = Input::get('parent', 0);
        $url = Input::get('url', '');
        $level = Input::get('level', 0);
        $parent_path = Input::get('parent_path', '');

        try {
            $menu = new SysMenu();
            $menu->m_name = $name;
            $menu->m_memo = $desc;
            $menu->m_parent = $parent;
            $menu->m_url = $url;
            $menu->m_path = $parent_path;
            $menu->m_level = $level;
            $menu->addMenu();
            $re = Tools::reTrue('添加菜单成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加菜单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putMenu($id)
    {
        $name = Input::get('name', '');
        $desc = Input::get('desc', '');
        $parent = Input::get('parent', 0);
        $url = Input::get('url', '');
        $level = Input::get('level', 0);
        $parent_path = Input::get('parent_path', '');

        try {
            $menu = SysMenu::find($id);
            $menu->m_name = $name;
            $menu->m_memo = $desc;
            $menu->m_parent = $parent;
            $menu->m_url = $url;
            $menu->m_path = $parent_path;
            $menu->m_level = $level;
            $menu->save();
            $re = Tools::reTrue('修改菜单成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '修改菜单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delMenu($id)
    {
        $menu = SysMenu::find($id);
        if (!empty($menu)) {
            $menu->delete();
        }
        return Tools::reTrue('删除成功');
    }

    public function postRole()
    {
        $name = Input::get('name', '');
        $desc = Input::get('desc', '');
        try {
            $role = new SysRole();
            $role->r_name = $name;
            $role->r_memo = $desc;
            $role->save();
            $re = Tools::reTrue('添加角色成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加角色失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listRole()
    {
        $per_page = Input::get('per_page', 10000000);
        try {
            $list = SysRole::paginate($per_page);
            $data['rows'] = [];
            foreach ($list as $key => $role) {
                $data['rows'][] = $role->showInList();
            }
            $data['total'] = $list->getTotal();
            $re = Tools::reTrue('获取角色成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取角色失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putRole($id)
    {
        $name = Input::get('name', '');
        $desc = Input::get('desc', '');
        try {
            $role = SysRole::find($id);
            if (empty($role)) {
                throw new Exception("么有找到请求的角色", 10001);
            }
            $role->r_name = $name;
            $role->r_memo = $desc;
            $role->save();
            $re = Tools::reTrue('修改成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '修改失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delRole($id)
    {
        $role = SysRole::find($id);
        if (!empty($role)) {
            $role->delete();
        }
        return Tools::reTrue('删除成功');
    }

    public function addRoleMenu($id)
    {
        $menus = Input::get('menus', '');
        try {
            SysRoleMenu::clearRoleMenu($id);
            if (!empty($menus)) {
                $role = SysRole::find($id);
                if (!is_array($menus)) {
                    $menus = explode(',', $menus);
                }
                foreach ($menus as $key => $menu) {
                    if (!$menu) {
                        continue;
                    }
                    $role->addMenu($menu);
                }
            }
            $re = Tools::reTrue('添加菜单成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加菜单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delRoleMenu($id)
    {
        $menus = Input::get('menus', '');
        try {
            $role = SysRole::find($id);
            $menus = explode(',', $menus);
            foreach ($menus as $id) {
                if (!is_numeric($id)) {
                    throw new Exception("包含无效的菜单数据-".$id, 10001);
                }
            }
            $role->delMenus($menus);
            $re = Tools::reTrue('删除菜单成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除菜单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listUserMenu($id)
    {
        try {
            $menu_tree = SysMenu::getTreeByAdmin($id);
            $re = Tools::reTrue('获取菜单成功', $menu_tree);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取菜单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listRoleMenu($id)
    {
        try {
            $menu_tree = SysMenu::getTreeByRole($id);
            $re = Tools::reTrue('获取菜单成功', $menu_tree);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取菜单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listRoleUser($id)
    {
        try {
            $list = SysUserRole::with(['user'])->where('r_id', '=', $id)->get();
            $data = [];
            foreach ($list as $key => $role) {
                $data[] = $role->user->showInList();
            }
            $re = Tools::reTrue('获取admin用户成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取admin用户失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postRoleUser($id)
    {
        $users = Input::get('users', '');
        try {
            SysUserRole::clearRoleUser($id);
            if (!empty($users)) {
                if (!is_array($users)) {
                    $role = SysRole::find($id);
                    if (empty($role)) {
                        throw new Exception("没有找到请求的角色", 1);
                    }
                    $users = explode(',', $users);
                    foreach ($users as $key => $user) {
                        if (!$user) {
                            continue;
                        }
                        $role->addUser($user);
                    }
                }
            }
            $re = Tools::reTrue('设置用户成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '设置用户失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
