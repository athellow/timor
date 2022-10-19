<?php
declare (strict_types = 1);

namespace app\model\user;

use app\model\BaseModel;
use think\model\concern\SoftDelete;

class User extends BaseModel
{
    use SoftDelete;
    
    /**
     * 插入前加密密码
     * 
     * @param User $data
     * @return void
     */
    public static function onBeforeInsert($data): void
    {
        $data->password = (new self)->encryptPassword($data->password);
    }

    /**
     * 更新时判断是否需要密码加密
     * 
     * @param User $data
     * @return void
     */
    public static function onBeforeUpdate($data): void
    {
        // $old = (new self())->where('id', '=', $data->id)->findOrEmpty();
        
        // if ($data->password && $data->password !== $old->password) {
        //     $data->password = (new self)->encryptPassword($data->password);
        // }
    }

    /**
     * 加密密码
     *
     * @param  string $password 参数
     * @return string
     */
    protected function encryptPassword($password): string
    {
        return base64_encode(password_hash($password, 1));
    }
}