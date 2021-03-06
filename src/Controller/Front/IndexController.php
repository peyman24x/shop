<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

namespace Module\Shop\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Pi\Paginator\Paginator;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Json\Json;

class IndexController extends ActionController
{
	public function indexAction()
    {
        // Get info from url
        $module = $this->params('module');
        // Get config
        $config = Pi::service('registry')->config->read($module);
        // Set product info
        $where = array('status' => 1);
        // Get product List
        $productList = $this->productList($where);
        // Set paginator info
        $template = array(
            'controller' => 'index',
            'action' => 'index',
            );
        // Get paginator
        $paginator = $this->productPaginator($template, $where);
        // category list
        $category = Pi::api('shop', 'category')->categoryList(0);
        // Get special
        if ($config['view_special']) {
            $specialList = Pi::api('shop', 'special')->getAll();
            $this->view()->assign('specialList', $specialList);
            $this->view()->assign('specialTitle', __('Special products'));
        }
        // Set view
    	$this->view()->setTemplate('product_list');
        $this->view()->assign('productList', $productList);
        $this->view()->assign('productTitle', __('New products'));
        $this->view()->assign('categories', $category);
        $this->view()->assign('paginator', $paginator);
        $this->view()->assign('config', $config);

        Pi::api('shop', 'user')->findUser();
    }

    public function productList($where)
    {
        // Set info
        $id = array();
        $page = $this->params('page', 1);
        $module = $this->params('module');
        $sort = $this->params('sort', 'create');
        $stock = $this->params('stock');
        $offset = (int)($page - 1) * $this->config('view_perpage');
        $limit = intval($this->config('view_perpage'));
        $order = $this->setOrder($sort);
        // Set show just have stock
        if (isset($stock) && $stock == 1) {
            $where['stock'] = 1;
        }
        // Set info
        $columns = array('product' => new Expression('DISTINCT product'));
        // Get info from link table
        $select = $this->getModel('link')->select()->where($where)->columns($columns)
        ->order($order)->offset($offset)->limit($limit);
        $rowset = $this->getModel('link')->selectWith($select)->toArray();
        // Make list
        foreach ($rowset as $id) {
            $productId[] = $id['product'];
        }
        // Set info
        $where = array('status' => 1, 'id' => $productId);
        // Get category list
        $categoryList = Pi::api('shop', 'category')->categoryList();
        // Get list of product
        $select = $this->getModel('product')->select()->where($where)->order($order);
        $rowset = $this->getModel('product')->selectWith($select);
        foreach ($rowset as $row) {
            $product[$row->id] = Pi::api('shop', 'product')->canonizeProduct($row, $categoryList);
        }
        // return product
        return $product;
    }

    public function searchList($where)
    {
        // Set info
        $id = array();
        $page = $this->params('page', 1);
        $module = $this->params('module');
        $sort = $this->params('sort', 'create');
        $stock = $this->params('stock');
        $offset = (int)($page - 1) * $this->config('view_perpage');
        $limit = intval($this->config('view_perpage'));
        $order = $this->setOrder($sort);
        // Set show just have stock
        if (isset($stock) && $stock == 1) {
            $where['stock'] > 0;
        }
        // Get category list
        $categoryList = Pi::api('shop', 'category')->categoryList();
        // Get list of product
        $select = $this->getModel('product')->select()->where($where)
        ->order($order)->offset($offset)->limit($limit);
        $rowset = $this->getModel('product')->selectWith($select);
        foreach ($rowset as $row) {
            $product[$row->id] = Pi::api('shop', 'product')->canonizeProduct($row, $categoryList);
        }
        // return product
        return $product;   
    }

    public function productPaginator($template, $where)
    {
        $template['module'] = $this->params('module');
        $template['sort'] = $this->params('sort');
        $template['stock'] = $this->params('stock');
        $template['page'] = $this->params('page', 1);
        // get count     
        $columns = array('count' => new Expression('count(DISTINCT `product`)'));
        $select = $this->getModel('link')->select()->where($where)->columns($columns);
        $template['count'] = $this->getModel('link')->selectWith($select)->current()->count;
        // paginator
        $paginator = $this->canonizePaginator($template);
        return $paginator;
    }

    public function searchPaginator($template, $where)
    {
        $template['module'] = $this->params('module');
        $template['sort'] = $this->params('sort');
        $template['stock'] = $this->params('stock');
        $template['page'] = $this->params('page', 1);
        // get count     
        $columns = array('count' => new Expression('count(*)'));
        $select = $this->getModel('product')->select()->where($where)->columns($columns);
        $template['count'] = $this->getModel('product')->selectWith($select)->current()->count;
        // paginator
        $paginator = $this->canonizePaginator($template);
        return $paginator;
    }

    public function canonizePaginator($template)
    {
        // paginator
        $paginator = Paginator::factory(intval($template['count']));
        $paginator->setItemCountPerPage(intval($this->config('view_perpage')));
        $paginator->setCurrentPageNumber(intval($template['page']));
        $paginator->setUrlOptions(array(
            'router'    => $this->getEvent()->getRouter(),
            'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
            'params'    => array_filter(array(
                'module'        => $this->getModule(),
                'controller'    => $template['controller'],
                'action'        => $template['action'],
                'sort'          => $template['sort'],
                'stock'         => $template['stock'],
            )),
        ));
        return $paginator;
    }

    public function setOrder($sort = 'create')
    {
        // Set order
        switch ($sort) {
            case 'stock':
                $order = array('stock DESC', 'id DESC');
                break;

            case 'price':
                $order = array('price DESC', 'id DESC');
                break; 
                
            case 'update':
                $order = array('time_update DESC', 'id DESC');
                break; 

            case 'create':
            default:
                $order = array('time_create DESC', 'id DESC');
                break;
        } 
        return $order;
    }
}