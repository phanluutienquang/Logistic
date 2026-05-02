<?php
declare (strict_types=1);

namespace app\api\controller;

use app\api\service\passport\Login as LoginService;

/**
 * 用户认证模块
 * Class Passport
 * @package app\api\controller
 */
class Passport extends Controller
{
    public function getCode(){
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx483d93f2c89fb198&redirect_uri=http%3A%2F%2Fzhuanyun.sllowly.cn/index.php?s=/api/passport/loginwx&wxapp_id=10001&response_type=code&scope=snsapi_userinfo&state=10001#wechat_redirect";
        
        return $url;
    }

     protected function postForm()
    {
        return $this->request->post();
    }
    
    public function register(){
        $LoginService = new LoginService;
        $data = $this->postData();
        $data['wxapp_id'] = $this->wxapp_id;
        if (!$LoginService->registerMobile($data)) {
            return $this->renderError($LoginService->getError());
        }
        return $this->renderSuccess([],'注册成功，请前往登录');
    }
    
    /**
     * 找回密码
     * Class Passport
     * @package app\api\controller
     */
    public function findpassword(){
        $LoginService = new LoginService;
     
        if (!$LoginService->findpassword($this->postData())) {
            return $this->renderError($LoginService->getError());
        }
        return $this->renderSuccess([],'重置成功，请前往登录');
    }

    
    /**
     * 登录接口 (需提交手机号、短信验证码、第三方用户信息)
     * @return array|\think\response\Json
     * @throws \app\common\exception\BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login()
    {
        // 执行登录
        $LoginService = new LoginService;
        if (!$LoginService->login($this->postData())) {
            return $this->renderError($LoginService->getError());
        }
        // 用户信息
        $userInfo = $LoginService->getUserInfo();
        return $this->renderSuccess([
            'userId' => (int)$userInfo['user_id'],
            'token' => $LoginService->getToken((int)$userInfo['user_id'])
        ], '登录成功');
    }

    /**
     * 微信小程序快捷登录 (需提交wx.login接口返回的code、微信用户公开信息)
     * 业务流程：判断openid是否存在 -> 存在:  更新用户登录信息 -> 返回userId和token
     *                          -> 不存在: 返回false, 跳转到注册页面
     * @return array|\think\response\Json
     * @throws \app\common\exception\BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loginMpWx()
    {
        // 微信小程序一键登录
        $LoginService = new LoginService;
        if (!$LoginService->loginMpWx($this->postForm())) {
            return $this->renderError($LoginService->getError());
        }
        // 获取登录成功后的用户信息
        $userInfo = $LoginService->getUserInfo();
        return $this->renderSuccess([
            'userId' => (int)$userInfo['user_id'],
            'token' => $LoginService->getToken((int)$userInfo['user_id'])
        ], '登录成功');
    }
    /**
 * @OA\Post(
 *     path="/passport/loginLine",
 *     tags={"Passport"},
 *     summary="Login via LINE (OpenID Connect)",
 *     description="Authenticate user using LINE idToken. Backend verifies token and auto login/register.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"idToken"},
 *             @OA\Property(
 *                 property="idToken",
 *                 type="string",
 *                 example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjE...",
 *                 description="LINE ID Token from LIFF (liff.getIDToken())"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login success",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=1),
 *             @OA\Property(property="msg", type="string", example="LINE login successful"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="userId", type="integer", example=10001),
 *                 @OA\Property(property="token", type="string", example="a8f9c2e3..."),
 *                 @OA\Property(property="line_user_id", type="string", example="Uxxxxxxxxxxxx")
 *             )
 *         )
 *     )
 * )
 */

 public function loginLine1()
{
    $LoginService = new LoginService;

    if (!$LoginService->loginMpLine($this->postForm())) {
        return $this->renderError($LoginService->getError());
    }

    $userInfo = $LoginService->getUserInfo();

    return $this->renderSuccess([
        'userId' => (int)$userInfo['user_id'],
        'token'  => $LoginService->getToken((int)$userInfo['user_id'])
    ], 'LINE login success');
}
    
    public function loginClerk()
    {
        // 微信小程序一键登录
        $LoginService = new LoginService;
        $data = $this->request->param();
        // dump($data);die;
        if (!$LoginService->loginMpWxMobileClerk($data)) {
            return $this->renderError($LoginService->getError());
        }
        // 获取登录成功后的用户信息
        $userInfo = $LoginService->getUserInfo();
        return $this->renderSuccess([
            'wxapp_id'=>$userInfo['wxapp_id'],
            'userId' => (int)$userInfo['user_id'],
            'token' => $LoginService->getToken((int)$userInfo['user_id'])
        ], '登录成功');
    }
    
    /**
     * 微信公众号快捷登录 (需提交wx.login接口返回的code、微信用户公开信息)
     * 业务流程：判断openid是否存在 -> 存在:  更新用户登录信息 -> 返回userId和token
     *                          -> 不存在: 返回false, 跳转到注册页面
     * @return array|\think\response\Json
     * @throws \app\common\exception\BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loginWxOfficial()
    {
        // 微信小程序一键登录
        $LoginService = new LoginService;
   
        if (!$LoginService->loginWxOfficial($this->postForm())) {
            return $this->renderError($LoginService->getError());
        }
        // 获取登录成功后的用户信息
        $userInfo = $LoginService->getUserInfo();
        return $this->renderSuccess([
            'userId' => (int)$userInfo['user_id'],
            'token' => $LoginService->getToken((int)$userInfo['user_id'])
        ], '微信授权登录成功');
    }

    /**
     * 快捷登录: 微信小程序授权手机号登录
     * @return array|\think\response\Json
     * @throws \app\common\exception\BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loginMpWxMobile()
    {
        // 微信小程序一键登录
        $LoginService = new LoginService;
        if (!$LoginService->loginMpWxMobile($this->request->param())) {
            return $this->renderError($LoginService->getError());
        }
        // 获取登录成功后的用户信息
        $userInfo = $LoginService->getUserInfo();
        return $this->renderSuccess([
            'userId' => (int)$userInfo['user_id'],
            'token' => $LoginService->getToken((int)$userInfo['user_id'])
        ], '登录成功');
    }
    /**
     * @OA\Post(
     *     path="/passport/loginMpZalo",
     *     tags={"Passport"},
     *     summary="Đăng nhập qua Zalo Mini App",
     *     description="Sử dụng zalo_user_id (openid) để đăng nhập hoặc đăng ký tự động",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="zalo_user_id", type="string", description="ID người dùng Zalo"),
     *             @OA\Property(property="nickname", type="string", description="Tên người dùng"),
     *             @OA\Property(property="avatar", type="string", description="Ảnh đại diện")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Đăng nhập thành công")
     * )
     */
    public function loginMpZalo()
    {
        $LoginService = new LoginService;
        if (!$LoginService->loginMpZalo($this->request->param())) {
            return $this->renderError($LoginService->getError());
        }
        $userInfo = $LoginService->getUserInfo();
        return $this->renderSuccess([
            'userId' => (int)$userInfo['user_id'],
            'token' => $LoginService->getToken((int)$userInfo['user_id']),
            'zalo_user_id' => $userInfo['open_id']
        ], 'Đăng nhập thành công');
    }

/**
 * @OA\Post(
 *     path="/passport/loginLine",
 *     tags={"Passport"},
 *     summary="Login via LINE (OpenID Connect)",
 *     description="Authenticate user using LINE idToken. Backend verifies token and auto login/register.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"idToken"},
 *             @OA\Property(
 *                 property="idToken",
 *                 type="string",
 *                 example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjE...",
 *                 description="LINE ID Token from LIFF (liff.getIDToken())"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login success",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=1),
 *             @OA\Property(property="msg", type="string", example="LINE login successful"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="userId", type="integer", example=10001),
 *                 @OA\Property(property="token", type="string", example="a8f9c2e3..."),
 *                 @OA\Property(property="line_user_id", type="string", example="Uxxxxxxxxxxxx")
 *             )
 *         )
 *     )
 * )
 */
public function loginLine()
{
    // 1. Get idToken from request
    $idToken = $this->request->param('idToken');

    if (empty($idToken)) {
        return $this->renderError("Missing idToken");
    }

    // 2. Load LINE Channel ID from config/env
    // $channelId = env('LINE_CHANNEL_ID'); // IMPORTANT: set this in .env
    $channelId = config('line_channel_id');

    if (empty($channelId)) {
        return $this->renderError("LINE_CHANNEL_ID not configured");
    }

    // 3. Verify idToken with LINE API
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.line.me/oauth2/v2.1/verify");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'id_token' => $idToken,
        'client_id' => $channelId
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);

    // Handle CURL error
    if ($response === false) {
        curl_close($ch);
        return $this->renderError("LINE verify request failed");
    }

    curl_close($ch);

    // 4. Decode response
    $data = json_decode($response, true);

    if (!is_array($data)) {
        return $this->renderError("Invalid LINE response");
    }

    // (Optional) Debug log
    // file_put_contents('line_verify.log', $response . PHP_EOL, FILE_APPEND);

    // 5. Validate token payload
    if (!isset($data['sub'])) {
        return $this->renderError("Invalid LINE token");
    }

    // Check audience (must match your Channel ID)
    if (!isset($data['aud']) || $data['aud'] !== $channelId) {
        return $this->renderError("Invalid audience");
    }

    // Check issuer
    if (!isset($data['iss']) || $data['iss'] !== 'https://access.line.me') {
        return $this->renderError("Invalid issuer");
    }

    // Check expiration
    if (!isset($data['exp']) || $data['exp'] < time()) {
        return $this->renderError("Token expired");
    }

    // 6. Extract user info from idToken
    $lineUserId = $data['sub'];            // unique LINE user ID
    $nickname   = $data['name'] ?? '';
    $avatar     = $data['picture'] ?? '';

    // 7. Call service to login/register user
    $LoginService = new Login;

    $params = [
        'line_user_id' => $lineUserId,
        'nickname'     => $nickname,
        'avatar'       => $avatar
    ];

    if (!$LoginService->loginMpLine($params)) {
        return $this->renderError($LoginService->getError());
    }

    // 8. Return your system token (JWT/session)
    $userInfo = $LoginService->getUserInfo();

    return $this->renderSuccess([
        'userId'       => (int)$userInfo['user_id'],
        'token'        => $LoginService->getToken((int)$userInfo['user_id']),
        'line_user_id' => $lineUserId
    ], 'LINE login successful');
}
}