"""Local XAMPP integration test. Removes only its uniquely named test records."""
import urllib.request,urllib.parse,urllib.error,http.cookiejar,re,subprocess,json,uuid,os
BASE=os.getenv('LOST_FOUND_TEST_URL','http://127.0.0.1/Initial-lost-found-project/')
PHP=os.getenv('PHP_BINARY','C:/xampp/php/php.exe')
def php(code):return subprocess.check_output([PHP,'-r',code],text=True).strip()
class Browser:
 def __init__(self):self.op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def req(self,path='',data=None):
  try:
   r=self.op.open(BASE+path,urllib.parse.urlencode(data).encode() if data is not None else None,timeout=15);return r.status,r.read().decode(),r.url
  except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
 def token(self,path):return re.search(r'name="csrf" value="([^"]+)"',self.req(path)[1])[1]
 def post(self,path,data,token_path=None):return self.req(path,{'csrf':self.token(token_path or path),**data})
a,b,admin,guest=Browser(),Browser(),Browser(),Browser()
prefix='gatewayqa-'+uuid.uuid4().hex[:10]
email=lambda suffix:prefix+'-'+suffix+'@campusmail.test'
password='Testing-passphrase-4827!'
snapshot=json.loads(php("require 'includes/auth.php';echo json_encode(db()->query('SELECT * FROM request_limits')->fetchAll());"))
def register(browser,suffix):return browser.post('register.php',{'name':prefix+' '+suffix,'email':email(suffix),'student_id':prefix+'-'+suffix,'password':password,'password_confirm':password,'role':'admin'})
def report(title):
 r=a.post('public/create.php',{'title':title,'description':'A unique QA keyring with identifying marks beneath the ring.','status':'found','category':'Keys','location':'QA Library','date_reported':'2026-11-11','contact_email':email('a'),'contact_phone':''})
 assert 'view.php?id=' in r[2],r[1][:400]
 return int(re.search(r'id=(\d+)',r[2])[1])
def action(action,id):return admin.post('admin_action.php',{'action':action,'id':id},'admin.php')
try:
 assert 'login.php' in guest.req()[2]
 for page in ['profile.php','admin.php','admin_users.php','public/create.php','public/claim.php?id=1','public/edit.php?id=1','public/delete.php?id=1','attachment.php?id=1','public/about.php']:
  assert 'login.php' in guest.req(page)[2],page
 assert 'admin@zppsu.edu.ph' in guest.req('login.php')[1] and 'password123' in guest.req('login.php')[1]
 assert guest.req('login.php',{'csrf':'invalid'})[0]==403
 assert 'index.php' in register(a,'a')[2]
 assert 'index.php' in register(b,'b')[2]
 assert 'admin.php' in admin.post('login.php',{'email':'admin@zppsu.edu.ph','password':'password123'})[2]
 for page in ['admin.php','admin_users.php','admin_user.php?id=1','public/edit.php?id=1','public/delete.php?id=1','attachment.php?id=1']:
  assert a.req(page)[0]==403,page
 assert a.post('admin_action.php',{'action':'archive','id':1},'profile.php')[0]==403
 assert 'profile-menu' in a.req()[1] and 'My Reports' in a.req()[1]
 item=report(prefix+' item one')
 assert b.req('public/view.php?id='+str(item))[0]==404
 assert '<article' not in b.req('?q='+prefix)[1]
 assert 'Changes saved successfully' in action('approve_report',item)[1]
 assert prefix+' item one' in b.req('?q='+prefix)[1]
 assert 'cannot claim your own' in a.post('public/claim.php?id='+str(item),{'proof':'The keys have unique identifying marks beneath the ring.'})[1]
 assert 'Claim submitted for review' in b.post('public/claim.php?id='+str(item),{'proof':'The keys have unique identifying marks beneath the ring.'})[1]
 assert 'already have a pending' in b.post('public/claim.php?id='+str(item),{'proof':'The keys have unique identifying marks beneath the ring.'})[1]
 assert prefix+' item one' in b.req('profile.php')[1]
 rid=php("require 'includes/auth.php';echo db()->query('SELECT id FROM recovery_requests WHERE item_id="+str(item)+"')->fetchColumn();")
 assert 'Changes saved successfully' in action('approve_claim',rid)[1]
 assert 'Approved / recovered' in b.req('profile.php')[1]
 assert 'already reviewed' in action('approve_claim',rid)[1]
 assert 'Changes saved successfully' in action('archive',item)[1]
 assert b.req('public/view.php?id='+str(item))[0]==404
 item2=report(prefix+' item two');assert 'Changes saved successfully' in action('reject_report',item2)[1]
 assert 'rejected' in a.req('profile.php')[1]
 # Admin edits are permission checked and preserve claim state.
 edit=admin.req('public/edit.php?id='+str(item2));assert edit[0]==200 and 'profile-menu' in edit[1]
 editdata={'title':prefix+' edited','description':'Updated QA details with a sufficiently long description.','location':'QA Engineering','date_reported':'2026-11-11','reporter_name':prefix+' a','category':'Books','contact_email':email('a'),'contact_phone':''}
 assert 'admin.php' in admin.post('public/edit.php?id='+str(item2),editdata)[2]
 assert prefix+' edited' in a.req('profile.php')[1]
 # Profile updates and password changes cannot promote the current student.
 assert 'Profile updated' in a.post('profile.php',{'action':'contact','name':prefix+' Updated','email':email('updated'),'phone':'09171234567','current_password':password,'role':'admin'})[1]
 assert a.req('admin.php')[0]==403
 other=Browser();assert 'index.php' in other.post('login.php',{'email':email('updated'),'password':password})[2]
 new='Changed-passphrase-7524!'
 assert 'Password changed' in a.post('profile.php',{'action':'password','current_password':password,'new_password':new,'password_confirm':new})[1]
 assert 'login.php' in other.req('profile.php')[2]
 # User management changes revoke sessions and never trust a student's submitted role.
 ids=json.loads(php("require 'includes/auth.php';$s=db()->prepare('SELECT id,email FROM accounts WHERE email IN (?,?)');$s->execute(['"+email('b')+"','admin@zppsu.edu.ph']);echo json_encode($s->fetchAll());"))
 bid=next(row['id'] for row in ids if row['email']==email('b'));aid=next(row['id'] for row in ids if row['email']=='admin@zppsu.edu.ph')
 ud={'action':'update_user','id':bid,'name':prefix+' b','email':email('b'),'student_id':prefix+'-b','role':'student','active':'0','current_password':'password123'}
 assert 'Changes saved successfully' in admin.post('admin_action.php',ud,'admin_users.php')[1]
 assert 'login.php' in b.req('profile.php')[2]
 assert 'inactive' in b.post('login.php',{'email':email('b'),'password':password})[1]
 ud['active']='1';ud['role']='admin';assert 'Changes saved successfully' in admin.post('admin_action.php',ud,'admin_users.php')[1]
 assert 'admin.php' in b.post('login.php',{'email':email('b'),'password':password})[2]
 ud['role']='student';assert 'Changes saved successfully' in admin.post('admin_action.php',ud,'admin_users.php')[1]
 assert 'login.php' in b.req('admin.php')[2]
 selfdisable={'action':'update_user','id':aid,'name':'Campus Administrator','email':'admin@zppsu.edu.ph','student_id':'','role':'admin','active':'0','current_password':'password123'}
 assert 'cannot deactivate or demote your own' in admin.post('admin_action.php',selfdisable,'admin_users.php')[1]
 assert a.req('logout.php')[0]==405
 assert 'login.php' in a.post('logout.php',{},'profile.php')[2]
 assert 'login.php' in a.req()[2]
 print('PASS: login gateway and demo roles, student registration, CSRF, role/ownership restrictions, report moderation, claims, admin editing/archiving, profile history, password/session invalidation, user enable/disable/promotion, self-lockout prevention and logout.')
finally:
 cleanup="""require 'includes/auth.php';$prefix=$argv[1].'%';$s=db()->prepare('SELECT a.id,a.user_id FROM accounts a JOIN users u ON u.id=a.user_id WHERE u.name LIKE ?');$s->execute([$prefix]);$ids=[];
 foreach($s->fetchAll() as $a){$ids[]=$a['id'];db()->prepare('DELETE FROM items WHERE reporter_id=?')->execute([$a['user_id']]);db()->prepare('DELETE FROM recovery_requests WHERE account_id=?')->execute([$a['id']]);db()->prepare('DELETE FROM accounts WHERE id=?')->execute([$a['id']]);db()->prepare('DELETE FROM users WHERE id=?')->execute([$a['user_id']]);}
 $snapshot=array_column(json_decode($argv[2],true),null,'bucket');$actions=['login','register','report','recovery'];foreach($ids as $id)$actions[]='profile:'.$id;
 foreach($actions as $action){$key=hash('sha256',$action.':127.0.0.1');if(isset($snapshot[$key]))db()->prepare('UPDATE request_limits SET hits=?,window_start=? WHERE bucket=?')->execute([$snapshot[$key]['hits'],$snapshot[$key]['window_start'],$key]);else db()->prepare('DELETE FROM request_limits WHERE bucket=?')->execute([$key]);}
 """
 subprocess.check_call([PHP,'-r',cleanup,prefix,json.dumps(snapshot)])
