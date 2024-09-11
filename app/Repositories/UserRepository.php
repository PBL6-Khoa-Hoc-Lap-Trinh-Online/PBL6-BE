<?php
namespace App\Repositories;
use App\Models\User;
class UserRepository extends BaseRepository implements UserInterface{
    public function getModel(){
        return User::class;
    }
    // public static function updateUser($id,$data){
    //     $user = (new self)->model->find($id);
    //     $user->user_fullname=''
    // }
  
}