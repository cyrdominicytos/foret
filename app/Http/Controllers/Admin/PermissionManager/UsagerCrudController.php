<?php

namespace App\Http\Controllers\Admin\PermissionManager;

use Backpack\CRUD\app\Http\Controllers\CrudController;
//use Backpack\PermissionManager\app\Http\Requests\UserStoreCrudRequest as StoreRequest;
//use Backpack\PermissionManager\app\Http\Requests\UserUpdateCrudRequest as UpdateRequest;
use App\Http\Requests\Admin\UsagerStoreCrudRequest as StoreRequest;
use App\Http\Requests\Admin\UsagerUpdateCrudRequest as UpdateRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\Poste;
use App\Models\Usager;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;

class UsagerCrudController extends \App\Http\Controllers\Admin\SuperAdminController {

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;

    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

//    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup() {
        $this->crud->setModel(config('backpack.permissionmanager.models.user'));
//        $this->crud->setEntityNameStrings(trans('backpack::permissionmanager.user'), trans('backpack::permissionmanager.users'));
        $this->crud->setEntityNameStrings(trans('backpack::permissionmanager.user'), 'Usagers');
        $this->crud->setRoute(backpack_url('usager'));


        //parent::setLayout();
        parent::gestionPermissionsAutres();
//        $this->crud->setDetailsRowView('admin.user.details_row');
        $this->crud->disableDetailsRow();

        $this->filtres();
    }

    public function setupListOperation() {
        $this->crud->setColumns([
            [
                'name' => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type' => 'text',
            ],
            [
                'name' => 'firstname',
                'label' => trans('backpack::permissionmanager.firstname'),
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type' => 'email',
            ],
            [
                'name' => 'telephone',
                'label' => 'Telephone',
                'type' => 'text',
            ],
            [
                'name' => 'reference_carte_professionnelle',
                'label' => 'Référence carte professionnelle',
                'type' => 'text',
            ],
            [
                'name' => 'reference_permis_coupe',
                'label' => 'Référence permis de coupe',
                'type' => 'text',
            ],
            
            [// n-n relationship (with pivot table)
                'label' => trans('backpack::permissionmanager.roles'), // Table column heading
                'type' => 'select_multiple',
                'name' => 'roles', // the method that defines the relationship in your Model
                'entity' => 'roles', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model' => config('permission.models.role'), // foreign key model
            ],
//            [// n-n relationship (with pivot table)
//                'label' => trans('backpack::permissionmanager.extra_permissions'), // Table column heading
//                'type' => 'select_multiple',
//                'name' => 'permissions', // the method that defines the relationship in your Model
//                'entity' => 'permissions', // the method that defines the relationship in your Model
//                'attribute' => 'name', // foreign key attribute that is shown to user
//                'model' => config('permission.models.permission'), // foreign key model
//            ],
        ]);

        $this->crud->addClause('whereHas', 'usager');
    }

    public function setupCreateOperation() {
        $this->addCreateUserFields();
        $this->crud->setValidation(StoreRequest::class);
    }

    public function setupUpdateOperation() {
        $this->addUpdateUserFields();
        $this->crud->setValidation(UpdateRequest::class);
    }

    /**
     * Store a newly created resource in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store() {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run
//------------------From backpack------------------------------------------
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();

        // insert item in the db
        $item = $this->crud->create($this->crud->getStrippedSaveRequest());
        
        $usager = new Usager();
        $usager->reference_carte_professionnelle = $request->reference_carte_professionnelle;
        $usager->reference_permis_coupe = $request->reference_permis_coupe;
        $usager->created_at = Carbon::now();
        $item->usager()->save($usager);
        event(new Registered($item));
        
        $this->data['entry'] = $this->crud->entry = $item;

        // show a success message
        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Update the specified resource in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update() {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run
        //------------------From backpack------------------------------------------
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        // update the row in the db
        $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
                $this->crud->getStrippedSaveRequest());
        $this->data['entry'] = $this->crud->entry = $item;
        
        $usager = $item->usager;
        $usager->reference_carte_professionnelle = $request->reference_carte_professionnelle;
        $usager->reference_permis_coupe = $request->reference_permis_coupe;
        $usager->updated_at = Carbon::now();
        $usager->save();

        // show a success message
        \Alert::success(trans('backpack::crud.update_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Handle password input fields.
     */
    protected function handlePasswordInput($request) {
        // Remove fields not present on the user.
        $request->request->remove('password_confirmation');
        $request->request->remove('roles_show');
        $request->request->remove('permissions_show');

        // Encrypt password if specified.
        if ($request->input('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
        } else {
            $request->request->remove('password');
        }

        return $request;
    }

    protected function addCreateUserFields() {
        $this->crud->addFields([
            [
                'name' => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '100',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ], // change the HTML attributes for the field wrapper - mostly for resizing fields 
            ],
            [
                'name' => 'firstname',
                'label' => trans('backpack::permissionmanager.firstname'),
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '100',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type' => 'email',
                'attributes' => [
                    'maxlength'=> '100',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'telephone',
                'label' => 'Telephone',
                'type' => 'number',
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'reference_carte_professionnelle',
                'label' => 'Référence carte professionnelle',
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '150',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'reference_permis_coupe',
                'label' => 'Référence permis de coupe',
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '150',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            
            [
                'name' => 'password',
                'label' => trans('backpack::permissionmanager.password'),
                'type' => 'password',
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'password_confirmation',
                'label' => trans('backpack::permissionmanager.password_confirmation'),
                'type' => 'password',
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            
            [// SelectMultiple = n-n relationship (with pivot table)
                'label' => trans('backpack::permissionmanager.roles'),
                'type' => 'select_multiple',
                'name' => 'roles', // the method that defines the relationship in your Model
                // optional
                'entity' => 'roles', // the method that defines the relationship in your Model
                'model' => config('permission.models.role'), // foreign key model
                'attribute' => 'display_name', // foreign key attribute that is shown to user
                'pivot' => true, // on create&update, do you need to add/delete pivot table entries?
                'allows_null' => false,
                // also optional
                'options' => (function ($query) {
                    return $query->orderBy('display_name', 'ASC')->whereIn('name',['Usager'])->get();
                }), // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            
//            [
//                // two interconnected entities
//                'label' => trans('backpack::permissionmanager.user_role_permission'),
//                'field_unique_name' => 'user_role_permission',
//                'type' => 'checklist_dependency',
//                'name' => ['roles', 'permissions'],
//                'subfields' => [
//                    'primary' => [
//                        'label' => trans('backpack::permissionmanager.roles'),
//                        'name' => 'roles', // the method that defines the relationship in your Model
//                        'entity' => 'roles', // the method that defines the relationship in your Model
//                        'entity_secondary' => 'permissions', // the method that defines the relationship in your Model
//                        'attribute' => 'display_name', // foreign key attribute that is shown to user
//                        'model' => config('permission.models.role'), // foreign key model
//                        'pivot' => true, // on create&update, do you need to add/delete pivot table entries?]
//                        'number_columns' => 3, //can be 1,2,3,4,6
//                    ],
//                    'secondary' => [
//                        'label' => ucfirst(trans('backpack::permissionmanager.permission_singular')),
//                        'name' => 'permissions', // the method that defines the relationship in your Model
//                        'entity' => 'permissions', // the method that defines the relationship in your Model
//                        'entity_primary' => 'roles', // the method that defines the relationship in your Model
//                        'attribute' => 'display_name', // foreign key attribute that is shown to user
//                        'model' => config('permission.models.permission'), // foreign key model
//                        'pivot' => true, // on create&update, do you need to add/delete pivot table entries?]
//                        'number_columns' => 3, //can be 1,2,3,4,6
//                    ],
//                ],
//            ],
        ]);
    }
    protected function addUpdateUserFields() {
        $this->crud->addFields([
            [
                'name' => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '100',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ], // change the HTML attributes for the field wrapper - mostly for resizing fields 
            ],
            [
                'name' => 'firstname',
                'label' => trans('backpack::permissionmanager.firstname'),
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '100',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'telephone',
                'label' => 'Telephone',
                'type' => 'number',
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type' => 'email',
                'attributes' => [
                    'maxlength'=> '100',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'reference_carte_professionnelle',
                'label' => 'Référence carte professionnelle',
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '150',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'reference_permis_coupe',
                'label' => 'Référence permis de coupe',
                'type' => 'text',
                'attributes' => [
                    'maxlength'=> '100',
                ], 
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],                        
            [
                'name' => 'password',
                'label' => trans('backpack::permissionmanager.password'),
                'type' => 'password',
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            [
                'name' => 'password_confirmation',
                'label' => trans('backpack::permissionmanager.password_confirmation'),
                'type' => 'password',
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            
            
            [// SelectMultiple = n-n relationship (with pivot table)
                'label' => trans('backpack::permissionmanager.roles'),
                'type' => 'select_multiple',
                'name' => 'roles', // the method that defines the relationship in your Model
                // optional
                'entity' => 'roles', // the method that defines the relationship in your Model
                'model' => config('permission.models.role'), // foreign key model
                'attribute' => 'display_name', // foreign key attribute that is shown to user
                'pivot' => true, // on create&update, do you need to add/delete pivot table entries?
                'allows_null' => false,
                // also optional
                'options' => (function ($query) {
                    return $query->orderBy('display_name', 'ASC')->whereIn('name',['Usager'])->get();
                }), // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
                'wrapper' => [
                    'class' => 'form-group col-md-4'
                ],
            ],
            
//            [
//                // two interconnected entities
//                'label' => trans('backpack::permissionmanager.user_role_permission'),
//                'field_unique_name' => 'user_role_permission',
//                'type' => 'checklist_dependency',
//                'name' => ['roles', 'permissions'],
//                'subfields' => [
//                    'primary' => [
//                        'label' => trans('backpack::permissionmanager.roles'),
//                        'name' => 'roles', // the method that defines the relationship in your Model
//                        'entity' => 'roles', // the method that defines the relationship in your Model
//                        'entity_secondary' => 'permissions', // the method that defines the relationship in your Model
//                        'attribute' => 'display_name', // foreign key attribute that is shown to user
//                        'model' => config('permission.models.role'), // foreign key model
//                        'pivot' => true, // on create&update, do you need to add/delete pivot table entries?]
//                        'number_columns' => 3, //can be 1,2,3,4,6
//                    ],
//                    'secondary' => [
//                        'label' => ucfirst(trans('backpack::permissionmanager.permission_singular')),
//                        'name' => 'permissions', // the method that defines the relationship in your Model
//                        'entity' => 'permissions', // the method that defines the relationship in your Model
//                        'entity_primary' => 'roles', // the method that defines the relationship in your Model
//                        'attribute' => 'display_name', // foreign key attribute that is shown to user
//                        'model' => config('permission.models.permission'), // foreign key model
//                        'pivot' => true, // on create&update, do you need to add/delete pivot table entries?]
//                        'number_columns' => 3, //can be 1,2,3,4,6
//                    ],
//                ],
//            ],
        ]);
    }

    protected function setupShowOperation() {
        // by default the Show operation will try to show all columns in the db table,
        // but we can easily take over, and have full control of what columns are shown,
        // by changing this config for the Show operation 
        $this->crud->set('show.setFromDb', false);

        // example logic
        $this->crud->addColumn([
            'name' => 'name',
            'label' => trans('backpack::permissionmanager.name'),
            'type' => 'text',
        ]);
        $this->crud->addColumn([
            'name' => 'firstname',
            'label' => trans('backpack::permissionmanager.firstname'),
            'type' => 'text',
        ]);
        $this->crud->addColumn([
            'name' => 'email',
            'label' => trans('backpack::permissionmanager.email'),
            'type' => 'email',
        ]);
        $this->crud->addColumn([
            'name' => 'reference_carte_professionnelle',
            'label' => 'Référence carte professionnelle',
            'type' => 'text',
        ]);
        $this->crud->addColumn([
            'name' => 'reference_permis_coupe',
            'label' => 'Référence permis de coupe',
            'type' => 'text',
        ]);       
        $this->crud->addColumn([
            'label' => trans('backpack::permissionmanager.roles'), // Table column heading
            'type' => 'select_multiple',
            'name' => 'roles', // the method that defines the relationship in your Model
            'entity' => 'roles', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model' => config('permission.models.role'), // foreign key model
        ]);
//        $this->crud->addColumn([
//            'label' => trans('backpack::permissionmanager.extra_permissions'), // Table column heading
//            'type' => 'select_multiple',
//            'name' => 'permissions', // the method that defines the relationship in your Model
//            'entity' => 'permissions', // the method that defines the relationship in your Model
//            'attribute' => 'name', // foreign key attribute that is shown to user
//            'model' => config('permission.models.permission'), // foreign key model
//        ]);

//        $this->crud->addColumn('text');
        // $this->crud->removeColumn('date');
        // $this->crud->removeColumn('extras');
        // Note: if you HAVEN'T set show.setFromDb to false, the removeColumn() calls won't work
        // because setFromDb() is called AFTER setupShowOperation(); we know this is not intuitive at all
        // and we plan to change behaviour in the next version; see this Github issue for more details
        // https://github.com/Laravel-Backpack/CRUD/issues/3108
    }

    /**
     * Liste des filtres
     *
     * @return void
     */
    private function filtres() {

        $roles = \App\Models\Role::pluck('name', 'id')->toArray();
        $this->crud->addFilter([
            'name' => 'role_id',
            'type' => 'select2',
            'label' => __('Rôle')
                ], function() use ($roles) {
            return $roles;
        }, function($value) { // if the filter is active
            $role = \App\Models\Role::find($value);
            $userIds = \App\Models\User::role($role)->pluck('users.id');
            $this->crud->addClause('whereIn', 'id', $userIds);
        });
    }

}
