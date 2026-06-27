<?php

namespace App\Http\Controllers;

use App\Models\FrontendFeature;
use App\Models\Package;
use App\Models\User;
use App\Models\Session;
use App\Models\School;
use App\Models\Faq;
use App\Models\WebsitePage;
use App\Models\WebsiteItem;
use App\Models\WebsiteSection;
use App\Models\WebsiteSetting;
use App\Models\WebsiteSeoSetting;
use Mail;
use App\Mail\SchoolEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function home(Request $request)
    {
        if(get_settings('frontend_view') == '1') {
            $packages = Package::where('status', 1)->get();
            $faqs = Faq::all();
            $users = User::all();
            $schools = School::all();
            $frontendFeatures = ($request->has('see_all')) 
            ? FrontendFeature::all() 
            : FrontendFeature::limit(8)->get();

            $websiteSections = collect();
            $websiteItems = collect();
            $websiteSettings = collect();
            $seo = null;

            if (
                Schema::hasTable('website_sections') &&
                Schema::hasTable('website_items') &&
                Schema::hasTable('website_settings') &&
                Schema::hasTable('website_seo_settings')
            ) {
                $websiteSections = WebsiteSection::where('status', 1)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->keyBy('section_key');

                $websiteItems = WebsiteItem::where('status', 1)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('section_key');

                $websiteSettings = WebsiteSetting::where('status', 1)
                    ->pluck('value', 'key');

                $seo = WebsiteSeoSetting::where('page_key', 'home')->where('status', 1)->first();
            }

            return view('frontend.landing_page', [
                'packages' => $packages,
                'faqs' => $faqs,
                'users' => $users,
                'schools' => $schools,
                'frontendFeatures' => $frontendFeatures,
                'websiteSections' => $websiteSections,
                'websiteItems' => $websiteItems,
                'websiteSettings' => $websiteSettings,
                'websiteSeo' => $seo,
            ]);
        } else {
            return redirect(route('login'));
        }
    }

    /**
     * Show website page by slug
     */
    public function websitePage($slug)
    {
        if(get_settings('frontend_view') != '1') {
            return redirect(route('login'));
        }

        $websitePage = null;
        $websiteSections = collect();
        $websiteItems = collect();
        $websiteSettings = collect();
        $allPages = collect();
        $websiteSeo = null;

        if (
            Schema::hasTable('website_pages') &&
            Schema::hasTable('website_sections') &&
            Schema::hasTable('website_items') &&
            Schema::hasTable('website_settings') &&
            Schema::hasTable('website_seo_settings')
        ) {
            $websitePage = \App\Models\WebsitePage::where('slug', $slug)
                ->where('status', 1)
                ->first();

            if (!$websitePage) {
                abort(404);
            }

            $allPages = \App\Models\WebsitePage::where('status', 1)
                ->orderBy('display_order')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $websiteSections = WebsiteSection::where('page_key', $websitePage->page_key)
                ->where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->keyBy('section_key');

            $websiteItems = WebsiteItem::where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('section_key');

            $websiteSettings = WebsiteSetting::where('status', 1)
                ->pluck('value', 'key');

            $websiteSeo = WebsiteSeoSetting::where('page_key', $websitePage->page_key)
                ->where('status', 1)
                ->first();
        }

        return view('frontend.website_page', [
            'websitePage' => $websitePage,
            'websiteSections' => $websiteSections,
            'websiteItems' => $websiteItems,
            'websiteSettings' => $websiteSettings,
            'allPages' => $allPages,
            'websiteSeo' => $websiteSeo,
        ]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function superadminHome()
    {
        return view('superadminHome');
    }
    public function adminDashboard()
    {
        return view('admin.dashboard');
    }


    public function schoolCreate(Request $request)
    {
        $data = $request->all();
        $school_email = $data['school_email'];
        $admin_email = $data['admin_email'];
        $duplicate_school_email_check = School::get()->where('email', $school_email);
        $duplicate_admin_email_check = User::get()->where('email', $admin_email);
        $recaptcha_secret = get_settings('recaptcha_secret_key');
        $recaptcha_switch = get_settings('recaptcha_switch_value');
        
        if($recaptcha_switch == 'Yes'){
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$data['g-recaptcha-response']);
        
                $response = json_decode($response, true);
            if($response['success'] === true){
                if(count($duplicate_school_email_check) == 0 && count($duplicate_admin_email_check) == 0) {
                $school = School::create([
                    'title' => $data['school_name'],
                    'email' => $data['school_email'],
                    'phone' => $data['school_phone'],
                    'address' => $data['school_address'],
                    'school_info' => $data['school_info'],
                    'status' => '2',
                ]);
    
                if (isset($school->id) && $school->id != "") {
    
                    $data['status'] = '1';
                    $data['session_title'] = date("Y");
                    $data['school_id'] = $school->id;
    
                    $session = Session::create($data);
    
                    School::where('id', $school->id)->update([
                        'running_session' => $session->id,
                    ]);
                    
                    if (!empty($data['photo'])) {
    
                        $imageName = time() . '.' . $data['photo']->extension();
    
                        $data['photo']->move(public_path('assets/uploads/user-images/'), $imageName);
    
                        $photo  = $imageName;
                    } else {
                        $photo = '';
                    }
                    $info = array(
                        'gender' => $data['gender'],
                        'blood_group' => $data['blood_group'],
                        'birthday' => isset($data['birthday'])? strtotime($data['birthday']):"",
                        'phone' => $data['admin_phone'],
                        'address' => $data['admin_address'],
                        'photo' => $photo
                    );
                    $data['user_information'] = json_encode($info);
                    User::create([
                        'name' => $data['admin_name'],
                        'email' => $data['admin_email'],
                        'password' => Hash::make($data['admin_password']),
                        'role_id' => '2',
                        'school_id' => $school->id,
                        'user_information' => $data['user_information'],
                        'status' => 1,
                    ]);
                }
                if(!empty(get_settings('smtp_user')) && (get_settings('smtp_pass')) && (get_settings('smtp_host')) && (get_settings('smtp_port'))){
                    Mail::to($data['admin_email'])->send(new SchoolEmail($data));
                }
    
                return redirect()->route('login')->with('message', 'School Created Successfully');
            } else {
                return redirect()->back()->with('warning','Some of the emails have been taken.');
            }
            }else{
            return redirect()->back()->with('warning', 'Something went wrong');
            }
        }else{
            if(count($duplicate_school_email_check) == 0 && count($duplicate_admin_email_check) == 0) {
                $school = School::create([
                    'title' => $data['school_name'],
                    'email' => $data['school_email'],
                    'phone' => $data['school_phone'],
                    'address' => $data['school_address'],
                    'school_info' => $data['school_info'],
                    'status' => '2',
                ]);
    
                if (isset($school->id) && $school->id != "") {
    
                    $data['status'] = '1';
                    $data['session_title'] = date("Y");
                    $data['school_id'] = $school->id;
    
                    $session = Session::create($data);
    
                    School::where('id', $school->id)->update([
                        'running_session' => $session->id,
                    ]);
                    
                    if (!empty($data['photo'])) {
    
                        $imageName = time() . '.' . $data['photo']->extension();
    
                        $data['photo']->move(public_path('assets/uploads/user-images/'), $imageName);
    
                        $photo  = $imageName;
                    } else {
                        $photo = '';
                    }
                    $info = array(
                        'gender' => $data['gender'],
                        'blood_group' => $data['blood_group'],
                        'birthday' => isset($data['birthday'])? strtotime($data['birthday']):"",
                        'phone' => $data['admin_phone'],
                        'address' => $data['admin_address'],
                        'photo' => $photo
                    );
                    $data['user_information'] = json_encode($info);
                    User::create([
                        'name' => $data['admin_name'],
                        'email' => $data['admin_email'],
                        'password' => Hash::make($data['admin_password']),
                        'role_id' => '2',
                        'school_id' => $school->id,
                        'user_information' => $data['user_information'],
                        'status' => 1,
                    ]);
                }
                if(!empty(get_settings('smtp_user')) && (get_settings('smtp_pass')) && (get_settings('smtp_host')) && (get_settings('smtp_port'))){
                    Mail::to($data['admin_email'])->send(new SchoolEmail($data));
                }
    
                return redirect()->route('login')->with('message', 'School Created Successfully');
            } else {
                return redirect()->back()->with('warning','Some of the emails have been taken.');
            }
        }
        
    }
    
    public function webRedirectToPayFee(Request $request)
    {
        // Remove the 'Basic ' prefix
        $base64Credentials = substr($request->query('auth'), 6);

        // Decode the base64-encoded string
        $credentials = base64_decode($base64Credentials);

        // Split the decoded string into email and password
        list($email, $password, $timestamp) = explode(':', $credentials);

        // Get the current timestamp
        $timestamp1 = strtotime(date('Y-m-d'));

        $difference = $timestamp1 - $timestamp;

        if($difference < 86400) {
            if (auth()->attempt(array('email' => $email, 'password' => $password))) {
                // Authentication passed...
                return redirect()->intended('/student/fee_manager/payment/'.$request->query('fee_id'));
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Invalid email or password',
            ]);
        } else {
            return redirect()->route('login')->withErrors([
                'email' => 'Token expired!',
            ]);
        }
    }

    /**
     * Download the institutional brochure
     */
    public function downloadBrochure()
    {
        $filePath = public_path('assets/uploads/documents/PRIME INTERNATIONAL INSTITUTE BROCHURE.pdf');
        
        if (!file_exists($filePath)) {
            abort(404, 'Brochure file not found');
        }
        
        return response()->download($filePath, 'PRIME INTERNATIONAL INSTITUTE BROCHURE.pdf');
    }
}
