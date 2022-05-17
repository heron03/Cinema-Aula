<?php
declare(strict_types=1);

namespace App\Controller;

class UsuariosController extends AppController
{
    public $paginate = [
        'fields' => ['id', 'nome'],
        'conditions' => ['Usuarios.deleted IS NULL'],
        'limit' => 10,
        'order' => ['nome' => 'asc']   
    ];

    public function beforeFilter(\Cake\Event\EventInterface $event) 
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login', 'logout']);    
    }             

    public function setPaginateConditions() 
    {
        $nome = '';
        if ($this->request->is('post')) {
            $nome = $this->request->getData('nome');
            $this->request->getSession()->write('nome', $nome);
        } else {
            $nome = $this->request->getSession()->read('nome');
        }
        if (!empty($nome)) {
            $this->paginate['conditions']['Usuarios.nome LIKE'] = '%' . trim($nome) . '%';
        }
    }

    public function getEditEntity($id) 
    {        
        $fields = ['id', 'nome', 'login'];
        $contain = [];
        
        return $this->Usuarios->get($id, compact('fields', 'contain'));
    }
    
    public function login() 
    {
        $this->Authorization->skipAuthorization();
        $this->viewBuilder()->setLayout('login');
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();
        // If the user is logged in send them away.
        if ($result->isValid()) {
            $this->UserLog->add([
                'action_id' => 1,
                'message' => 'Logado',
            ]);
            $target = $this->Authentication->getLoginRedirect() ?? '/';
            return $this->redirect($target);
        }
        if ($this->request->is('post') && !$result->isValid()) {
            $contador = $this->request->getSession()->read('Bloqueio');
            if (empty($contador)) {
                $contador = 0;
            }
            $contador++;
            $this->request->getSession()->write('Bloqueio', $contador);
            $this->UserLog->add([
                'action_id' => 1,
                'message' => 'Erro No Login ' . $this->request->getData()['login'],
                'user_id' => null,
            ]);
            $this->Flash->bootstrap(__('Usuário ou senha incorretos'), ['key' => 'danger']);            
        }        
    }

    public function logout() 
    {
        $this->Authorization->skipAuthorization();
        $this->Authentication->logout();
        
        return $this->redirect('/');    
    }
}
