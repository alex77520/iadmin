<?php
/**
 * Created by PhpStorm.
 * User: carl
 * Date: 2017/3/27
 * Time: 下午4:10
 */

namespace Services;


class PermissionsService extends ServiceAbstract
{
    public function model()
    {
        return \App\Models\SysPermissions::class;
    }

    public function all()
    {
        $list = $this->model->get();
        $data = getSubTree($list);
        return $data;
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function store(array $attributes)
    {
        $data['name']           = $attributes['name'];
        $data['display_name']   = $attributes['display_name'];
        $data['pid']            = $attributes['pid'];
        $data['sort']           = $attributes['sort'];
        $this->model->create($data);
        return true;
    }

    public function destroy($id)
    {
        $permission = $this->model->findOrFail($id);
        $permission->delete();
        return true;
    }


}