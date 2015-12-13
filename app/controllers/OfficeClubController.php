<?php
/**
*
*/
class OffieClubController extends \BaseController
{
    public function listClubs()
    {
        $per_page = Input::get('per_page', 100000);
        try {
            $list = Club::with(['user'])->paginate($per_page);
            $data = [];
            $data['rows'] = [];
            foreach ($list as $key => $club) {
                $data['rows'][] = $club->showInList();
            }
            $data['total'] = $list->getTotal();
            $re = Tools::reTrue('获取社团成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取社团失败:'.$e->getMessage());
        }
        return Responce::json($re);
    }
}
