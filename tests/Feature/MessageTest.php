<?php
namespace Test\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\kubiikslib\Helper;
use Artisan;
use App\User;
use App\Account;
use App\Notification;
use App\Message;

class MessageTest extends TestCase {

    //Database setup
    public function setUp() {
        parent::setUp();
        
        Mail::fake();        //Avoid sending emails
        Artisan::call('migrate');
    }

    //Clean up after the test
    public function tearDown() {
        parent::tearDown();
    }

    public function testMessageSendValidWithGroups() {
        $this->signup(["email"=>"sergi.redorta2@hotmail.com", "mobile"=>"0623133222"]);
        $this->signup(["email"=>"sergi.redorta3@hotmail.com", "mobile"=>"0623133233"]);
        $this->signup(["email"=>"sergi.redorta4@hotmail.com", "mobile"=>"0623133244"]);
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_ADMIN');
        $user->accounts()->save($account); 
        $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_ADMIN')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->post('api/groups/create', ['name'=>'test', 'description'=>'This is a long description because if not will fail']);
        $response  = $this->post('api/groups/attach', ['user_id'=>1, 'group_id'=>1]);
        $response  = $this->post('api/groups/attach', ['user_id'=>2, 'group_id'=>1]);
        $response  = $this->post('api/groups/attach', ['user_id'=>3, 'group_id'=>1]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
            "subject"   => "Message test2",
            "text"      => "This is a message test2",
            "to"        => ["users"=> [1], "groups"=>[1]]  //Array of users and groups with IDs
            ]);            
        //$user = User::find(1);
        //dd($user->messages()->get()->toArray());
        $this->assertDatabaseHas('messages', [
            'id' => 1,
            'user_id' => 1,
            'from_id' => 4,
            'to_user_list' => '1,2,3',
            'to_group_list' => "1"
        ]); 
    }




    public function testMessageValidAccountMemberOneSeveralUsers() {
        $this->signup(["email"=>"sergi.redorta2@hotmail.com", "mobile"=>"0623133222"]);
        $this->signup(["email"=>"sergi.redorta3@hotmail.com", "mobile"=>"0623133233"]);
        $this->signup(["email"=>"sergi.redorta4@hotmail.com", "mobile"=>"0623133244"]);
        $this->loginAsMember();
        $user = User::all()->last();
        //USER 4 sends message to 1 2 3
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
            "subject"   => "Message test",
            "text"      => "This is a message test",
            "to"        => ["users"=> [1,2,3]]//, "groups"=>[1]]  //Array of users and groups with IDs
            ]);
        //dd(Message::all()->toArray());
        $response->assertStatus(204);   //No error
        //Message exists
        $this->assertDatabaseHas('messages', [
            'id' => 1,
            'user_id' => 1,
            'from_id' => 4,
            'to_user_list' => '1,2,3',
            'text' => "This is a message test",
        ]); 
        $this->assertDatabaseHas('messages', [
            'id' => 2,
            'user_id' => 2,
            'from_id' => 4,
            'to_user_list' => '1,2,3',
            'text' => "This is a message test",
        ]); 
        $this->assertDatabaseHas('messages', [
            'id' => 3,
            'user_id' => 3,
            'from_id' => 4,
            'to_user_list' => '1,2,3',
            'text' => "This is a message test",
        ]); 
        $this->assertDatabaseMissing('messages', [
            'id' => 4
        ]);                 
    }

    //Parameter testing
    public function testMessageInValidParametersSubject() {
        $this->signup();
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 
        $this->login(['password'=>'Secure10','access' => Config::get('constants.ACCESS_MEMBER')]);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');

        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
            "subject"   => "",
            "text"      => "This is a message test",
            "to"        => ["users"=> [1]]//, "groups"=>[1]]  //Array of users and groups with IDs
        ]);
        $response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation.required']);
    }

    //Get all testing
    public function testMessageGetAllTwoMessagesSameSender() {
        $this->signup(["email"=>"sergi.redorta1@hotmail.com", "mobile"=>"0623133222"]); //1
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 

        $this->signup(["email"=>"sergi.redorta2@hotmail.com", "mobile"=>"0623133233"]); //2
        $this->signup(["email"=>"sergi.redorta3@hotmail.com", "mobile"=>"0623133244"]); //3
        $this->loginAsMember(); //4
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
            "subject"   => "Message test",
            "text"      => "This is a message test",
            "to"        => ["users"=> [1,2,3]]//, "groups"=>[1]]  //Array of users and groups with IDs
            ]);           
        //dd(DB::table('message_user')->get()->toArray());
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
                "subject"   => "Message test2",
                "text"      => "This is a message test2",
                "to"        => ["users"=> [1]]//, "groups"=>[1]]  //Array of users and groups with IDs
                ]);             
 //       dd(DB::table('message_user')->get()->toArray());

        $result = $this->logout();

        $this->token = null;
        $response = $this->withHeaders(['Authorization' => null])->post('api/auth/login',["email"=>"sergi.redorta1@hotmail.com", "password"=>"Secure10", "access"=>"Membre"] );
        $result =$response->json();
        $this->token = $result['token'];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->get('api/messages');
       //dd($response->json());
       $this->assertDatabaseHas('messages', [
        'id' => 1,
        'user_id' => 1,
        'from_id' => 4,
        'to_user_list' => '1,2,3',
        'text' => "This is a message test",
    ]); 
    $this->assertDatabaseHas('messages', [
        'id' => 2,
        'user_id' => 2,
        'from_id' => 4,
        'to_user_list' => '1,2,3',
        'text' => "This is a message test",
    ]); 
    $this->assertDatabaseHas('messages', [
        'id' => 3,
        'user_id' => 3,
        'from_id' => 4,
        'to_user_list' => '1,2,3',
        'text' => "This is a message test",
    ]); 
            //dd(Message::all()->toArray());
    $this->assertDatabaseMissing('messages', [
        'id' => 4,
        'user_id' => 1,
        'from_id' => 4,
        'text' => "this is a message test2"
    ]);  
        
    $this->assertDatabaseMissing('messages', [
        'id' => 5
    ]);  
        $response->assertStatus(200);
        //->assertJsonStructure(['id','subject','text','created_at', 'updated_at','from_id','from_first','from_last','isRead']);   //No error  
    }



    //Get all testing
    public function testMessageMarkAsReadTwoMessagesTwoSenders() {
        $this->signup(["email"=>"sergi.redorta1@hotmail.com", "mobile"=>"0623133222"]); //1
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 

        $this->signup(["email"=>"sergi.redorta2@hotmail.com", "mobile"=>"0623133233"]); //2
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 

        $this->signup(["email"=>"sergi.redorta3@hotmail.com", "mobile"=>"0623133244"]); //3
        $this->loginAsMember(); //4
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        //USER 4 FOR SENDS TO 1,2,3
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
            "subject"   => "Message test",
            "text"      => "This is a message test",
            "to"        => ["users"=> [1,2,3]]//, "groups"=>[1]]  //Array of users and groups with IDs
            ]);           

        $result = $this->logout();

        $this->token = null;
        $response = $this->withHeaders(['Authorization' => null])->post('api/auth/login',["email"=>"sergi.redorta2@hotmail.com", "password"=>"Secure10", "access"=>"Membre"] );
        $result =$response->json();
        $this->token = $result['token'];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        //USER 2 SENDS TO 1
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
                "subject"   => "Message test2",
                "text"      => "This is a message test2",
                "to"        => ["users"=> [1]]//, "groups"=>[1]]  //Array of users and groups with IDs
                ]);             
        $result = $this->logout();

        //CONNECT AS 1 EXPECTS 1 MESSAGE FROM USER 4 and ONE FROM 2
        $this->token = null;
        $response = $this->withHeaders(['Authorization' => null])->post('api/auth/login',["email"=>"sergi.redorta1@hotmail.com", "password"=>"Secure10", "access"=>"Membre"] );
        $result =$response->json();
        $this->token = $result['token'];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/markread', ['id'=>4]);  
        $user = User::find(1);
        //dd(Message::all()->toArray());
        //dd(DB::table('message_user')->get()->toArray());
        //Message exists
        $this->assertDatabaseHas('messages', [
            'id' => 4,
            'user_id' => 1,
            'from_id' => 2,
            'isRead' => true
        ]); 

        //dd($response->json());    
        $response->assertStatus(204);
        //->assertJsonStructure(['id','subject','text','created_at', 'updated_at','from_id','from_first','from_last','isRead']);   //No error  
    }


    //Get all testing
    public function testMessageDeleteTwoMessagesTwoSendersDeleteMessage() {
        $this->signup(["email"=>"sergi.redorta1@hotmail.com", "mobile"=>"0623133222"]); //1
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 

        $this->signup(["email"=>"sergi.redorta2@hotmail.com", "mobile"=>"0623133233"]); //2
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 

        $this->signup(["email"=>"sergi.redorta3@hotmail.com", "mobile"=>"0623133244"]); //3
        $this->loginAsMember(); //4
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        //USER 4 SENDS TO 1,2,3
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
            "subject"   => "Message test",
            "text"      => "This is a message test",
            "to"        => ["users"=> [1,2,3]]//, "groups"=>[1]]  //Array of users and groups with IDs
            ]);           

        $result = $this->logout();

        $this->token = null;
        $response = $this->withHeaders(['Authorization' => null])->post('api/auth/login',["email"=>"sergi.redorta2@hotmail.com", "password"=>"Secure10", "access"=>"Membre"] );
        $result =$response->json();
        $this->token = $result['token'];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        //USER 2 SENDS TO 1
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
                "subject"   => "Message test2",
                "text"      => "This is a message test2",
                "to"        => ["users"=> [1]]//, "groups"=>[1]]  //Array of users and groups with IDs
                ]);             
 //       dd(DB::table('message_user')->get()->toArray());

        $result = $this->logout();

        //CONNECT AS 1 EXPECTS 1 MESSAGE FROM USER 4 and ONE FROM 2
        $this->token = null;
        $response = $this->withHeaders(['Authorization' => null])->post('api/auth/login',["email"=>"sergi.redorta1@hotmail.com", "password"=>"Secure10", "access"=>"Membre"] );
        $result =$response->json();
        $this->token = $result['token'];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/message/delete', ['id'=>4]);  
        $user = User::find(1);
        
        //WE EXPECT ONLY MESSAGE 1 FROM USER 4 AND MESSAGE 2 NOWHERE
        //dd(DB::table('message_user')->get()->toArray());
        $this->assertDatabaseHas('messages', [
            'id' => 1,
            'user_id' => 1,
            'from_id' => 4,
            'text' => "This is a message test",
        ]); 
        $this->assertDatabaseMissing('messages', [
            'id' => 4,
            'from_id' => 2
        ]); 
        $response->assertStatus(204);
    }




    //User delete, messages deleted
    public function testMessageDeleteUserDelete() {
        $this->signup(["email"=>"sergi.redorta1@hotmail.com", "mobile"=>"0623133222"]); //1
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 

        $this->signup(["email"=>"sergi.redorta2@hotmail.com", "mobile"=>"0623133233"]); //2
        $user = User::all()->last();
        $account = new Account;
        $account->user_id = $user->id;
        $account->key = Helper::generateRandomStr(30);
        $account->password = Hash::make('Secure10', ['rounds' => 12]);
        $account->access = Config::get('constants.ACCESS_MEMBER');
        $user->accounts()->save($account); 

        $this->signup(["email"=>"sergi.redorta3@hotmail.com", "mobile"=>"0623133244"]); //3
        $this->loginAsMember(); //4
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        //USER FOR SENDS TO 1,2,3
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
            "subject"   => "Message test",
            "text"      => "This is a message test",
            "to"        => ["users"=> [1,2,3]]//, "groups"=>[1]]  //Array of users and groups with IDs
            ]);           

        $result = $this->logout();

        $this->token = null;
        $response = $this->withHeaders(['Authorization' => null])->post('api/auth/login',["email"=>"sergi.redorta2@hotmail.com", "password"=>"Secure10", "access"=>"Membre"] );
        $result =$response->json();
        $this->token = $result['token'];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        //USER 2 SENDS TO 1
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', [
                "subject"   => "Message test2",
                "text"      => "This is a message test2",
                "to"        => ["users"=> [1]]//, "groups"=>[1]]  //Array of users and groups with IDs
                ]);             
        $result = $this->logout();

        //CONNECT AS 1 EXPECTS 1 MESSAGE FROM USER 4 and ONE FROM 2
        $this->token = null;
        $response = $this->withHeaders(['Authorization' => null])->post('api/auth/login',["email"=>"sergi.redorta1@hotmail.com", "password"=>"Secure10", "access"=>"Membre"] );
        $result =$response->json();
        $this->token = $result['token'];
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::find(1);
        //DELETE USER AND CHECK DB
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/auth/delete');
        //MESSAGE 2 NOWERE
        //MESSAGE 1 NO ATTACHED TO USER 1 AS IT HAS BEEN DELETED

        $this->assertDatabaseMissing('messages', [
            'user_id' => 4
        ]); 

        $response->assertStatus(204);
    }

    //Guard testing
    public function testMessageInvalidGuardAccountDefault() {
        $this->loginAs();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->get('api/auth/user');
        $user = User::all()->last();
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', []);
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'auth.member_required']);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/markread', []);
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'auth.member_required']);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->delete('api/message/delete', []);
        $response->assertStatus(401)->assertExactJson(['response' => 'error', 'message' => 'auth.member_required']);
    }

    public function testMessageInvalidGuardNotLoggedIn() {
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])->post('api/message/send', []);
        dd($response->json());
        //$response->assertStatus(400)->assertExactJson(['response' => 'error', 'message' => 'validation.required']);
    }



}



