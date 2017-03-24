<?php
/**
 * Created by PhpStorm.
 * User: carl
 * Date: 2017/3/21
 * Time: 下午2:37
 */

namespace Services;


class MenuService extends ServiceAbstract
{
    public static $dataTree;

    private $currentController;

    private $currentAction;

    private $controllerNameSpace = 'App.Http.Controllers.';

    public function model()
    {
        return 'App\Models\SysMenus';
    }

    /**
     * 初始化,分析路由中的controller与action
     */
    public function _init()
    {
        //当前控制器
        $route = \Route::currentRouteAction();

        list($this->currentController, $this->currentAction) = explode('@', $route);

        $this->currentController = str_replace('\\', '.', $this->currentController);
//        dump('!!'.$this->currentController.'!!');
    }

    /**
     * insert new data
     * @param array $request
     * @return bool
     */
    public function store(array $data)
    {
        $id = $this->model->create($data);

        if (!$id) {
            return false;
        }

        //更新缓存
        $this->setCache();
        return true;
    }

    /**
     * update the data
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function update(array $data, int $id)
    {
        $result = $this->model->where('id', $id)->update($data);
        if (!$result) {
            return false;
        }
        //更新缓存
        $this->setCache();
        return true;
    }

    /**
     * find a data by ID
     * @param $id
     * @return mixed
     */
    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * 获取菜单, 返回菜单html代码
     * @return string
     */
    public function getMenus()
    {
        $list = $this->getList();

        self::$dataTree = $this->getTree($list->toArray());

        $html = $this->getHtml(self::$dataTree);

        return $html;
    }

    public function delete($id)
    {
        $menu = $this->model->findOrFail($id);
        $result = $menu->delete();
        if (!$result) {
            return false;
        }
        $this->setCache();
        return true;
    }

    /**
     * 获取菜单,返回带有层级的数组
     * @return array
     */
    public function getMenusTwo()
    {
        $list = $this->getList();
        return $this->getSubTree($list);
    }

    /**
     * 从数据库中获取菜单数据列表
     * @return mixed
     */
    public function getList()
    {
        return \Cache::remember('menus:list', 86400, function () {
            return  $this->model->orderBy('sort', 'desc')->orderBy('id', 'asc')->get();
        });
    }

    /**
     * 更新菜单缓存
     */
    public function setCache()
    {
        \Cache::forget('menus:list');
        $this->getList();
    }

    /**
     * 获取父子结构数组
     * @param $data
     * @param int $pid
     * @return array
     */
    public function getTree($data, $pid=0)
    {
        $tree = [];

        foreach ($data as $row) {

            if ($row['pid'] == $pid) {

                $child = $this->getTree($data, $row['id']);

                if ($child) {
                    $row['child'] = $child;
                }

                $tree[] = $row;
            }

        }
        return $tree;
    }

    /**
     * 获取不带有child的数组结构
     * @param $data
     * @param int $pid
     * @param int $lev
     * @return array
     */
    public static function getSubTree(&$data , $pid = 0 , $lev = 1) {
        static $son = array();
        foreach($data as $key => $value) {
            if($value['pid'] == $pid) {
                $value['lev'] = $lev;
                $son[] = $value;
                unset($data[$key]);
                self::getSubTree($data , $value['id'] , $lev+1);
            }
        }
        return $son;
    }

    /**
     * 获取菜单结构HTML
     * @param $trees
     * @param bool $child
     * @return string
     */
    public function getHtml($trees, $child=false)
    {
        $html = '';
        foreach ($trees as $tree) {
            if (empty($tree['child'])) {
                $url = ($tree['uri'] !== '#') ? route($tree['uri']) : '#';
                $active = ($this->currentController == $this->controllerNameSpace. $tree['name']) ? 'layui-this' : '';
                if ($child) {
                    $html .= '<dd class="' . $active . '"><a href="' . $url . '"><cite> ' . $tree['display_name'] . ' </cite></a></dd>';
                } else {
                    $html .='<li class="layui-nav-item '.$active.'"><a href="'. $url .'"><cite>'.$tree['display_name'].'</cite></a></li>';
                }
            } else {
                $html .= '<li class="layui-nav-item layui-nav-itemed"><a href="javascript:;"><cite>'.$tree['display_name'].'</cite></a>';
                $html .= $this->getHtml($tree['child'], true);
                $html .= '</li>';
            }
        }

        if ($child) {
            return '<dl class="layui-nav-child">' . $html . '</dl>';
        } else {
            return '<ul class="layui-nav layui-nav-tree beg-navbar">' . $html . '<span class="layui-nav-bar"></span></ul>';
        }

    }

}