<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Modeller Controller
 * @author sg
 *
 */
class Kohana_Controller_Modeller extends Controller_Template {

    /**
     * The modeller object
     * @var Modeller
     */
    protected $_modeller;

    // -------------------------------------------------------------------------

    /**
     * Before processing
     */
    public function before()
    {
        parent::before();

        // create model by request params
        $this->_modeller = Modeller::factory(Inflector::singular($this->request->param('model')), $this->request->param('id'));

        // set the modeller base route
        $this->_modeller->base_route('modeller');
    }

    // -------------------------------------------------------------------------

    /**
     * After processing
     */
    public function after()
    {
        // call parent
        parent::after();
    }

    // -------------------------------------------------------------------------

    /**
     * Index action
     * @see Seso_Controller_Page::action_index()
     */
    public function action_index()
    {
        if ($this->request->method() == HTTP_Request::GET)
        {
            // generate list view
            $view = $this->_modeller->render_list($this->request->query());

            // set header title
            $this->template->header_title = $this->_modeller->model()->humanized_plural();

            // set view inside dashboard
            $this->template->areas('main', $view);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Add action
     */
    public function action_add()
    {
        if ($this->request->method() == HTTP_Request::POST)
        {
            // save entity on post request
            $this->_save_entity($this->_model);
        }

        // form view
        $view = View::factory('modeller/form');

        // set form of model
        $view->entity = $this->_modeller->model();

        $view->route = $this->route();

        // set form of model
        $view->form_view = View::factory( 'modeller/form', array('entity' => $this->_modeller->model()));

        // set view inside main area
        $this->template->areas('main', $view);
    }

    // -------------------------------------------------------------------------

    /**
     * Edit action
     */
    public function action_edit()
    {
        if (is_null($this->request->param('id')))
        {
            // edit request needs a id
            throw new Exception('Invalid request. Param "id" expected.');
            return;
        }

        // load model with id
        $model = ORM::factory($this->_modeller->model()->object_name(), $this->request->param('id'));

        if ($this->request->method() == HTTP_Request::POST)
        {
            // save entity on post request
            $this->_save_entity($this->model);
        }

        // form view
        $view = View::factory('modeller/form');

        // set form of model
        $view->entity = $model;

        // set form of model
        $view->form_view = View::factory('modeller/form', array('entity' => $model));

        // set view inside dashboard area
        $this->template->areas('main', $view);


        // generate has many connections
        $connections = array();

        foreach ($model->has_many() as $key => $values)
        {
            // connection factory
            $connection = ORM::factory(Inflector::singular($values['model']));

            // load content (list) of connection
            $content = Request::factory($this->route($connection))->query(array($values['foreign_key'] => $model->id))->execute()->body();

            // set has many connection
            $connections[$connection->object_name()] = array('title' => ucwords(Inflector::humanize($key)), 'content' => $content);
        }

        $view->connections = $connections;
        $view->route = $this->route();

        $this->template->areas('main')->connections =  $connections;
    }

    // -------------------------------------------------------------------------

    /**
     * Delete action
     */
    public function action_delete()
    {
        if (is_null($this->request->param('id')))
        {
            // request needs id
            throw new Exception('Invalid request. Param "id" expected.');
            return;
        }

        // load model
        $model = ORM::factory($this->_modeller->model()->object_name(), $this->request->param('id'));

        // delete entity
        $model->delete();

        // make the default get request
        $this->_redirect_to_list();
    }

    // -------------------------------------------------------------------------

    /**
     * Save the entity
     */
    protected function _save_entity(&$model)
    {
        // set values
        $model->values($this->request->post());

        // save entity
        $model->save();

        // make the default get request
        $this->_redirect_to_list();
    }

    // -------------------------------------------------------------------------

    /**
     * Redirect to list page
     */
    protected function _redirect_to_list($message='')
    {
        $redirect = $this->route();

        if ( ! is_null($this->request->query('redirect_to')))
        {
            // set redirect url from query
            $redirect = $this->request->query('redirect_to');
        }

        if ( ! is_null($this->request->post('redirect_to')))
        {
            // set redirect url from post
            $redirect = $this->request->post('redirect_to');
        }

        // redirect to url
        $this->redirect(BASE_URL.$redirect);
    }

    // -------------------------------------------------------------------------

    /**
     * Return belong to connections for breadcrumb
     */
    public function base_breadcrumbs($model = NULL)
    {
        if (is_null($model))
        {
            // set this model as default
            $model = $this->_modeller->model();
        }

        $breadcrumbs = array();

        while (count($model->belongs_to()) > 0)
        {
            // get first connection in belongs to
            $belongs_to = key($model->belongs_to());

            // load model
            $model = $model->$belongs_to;

            if ($model->loaded())
            {
                // add model detail to beginning of base modeller breadcrumbs if loaded
                array_unshift($breadcrumbs, array('title' => $model, 'route' => $this->route($model).'/edit/'.$model->pk()));
            }
            // add model overview to beginning of base modeller breadcrumbs
            array_unshift($breadcrumbs, array('title' => $model->humanized_plural(), 'route' => $this->route($model)));
        }

        // add current route
        $breadcrumbs[] = array('title' => $this->_modeller->model()->humanized_plural(), 'route' => $this->route());

        // return breadcrumbs
        return $breadcrumbs;
    }

    // -------------------------------------------------------------------------

    /**
     * Create the route for a specific model
     *
     * @param  ORM  model
     * @return array
     */
    public function route($model = NULL)
    {
        if (is_null($model))
        {
            // set this model as default
            $model = $this->_modeller->model();
        }

        $parts = explode('_', $model->object_name());

        foreach ($parts as &$part)
        {
            $part = Inflector::singular($part);
        }

        // return route for model
        return 'modeller'.'/'.implode('_', $parts);
    }

    // -------------------------------------------------------------------------

}