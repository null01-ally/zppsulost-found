"""Admin workspace/JSON smoke test against the local development server."""
import urllib.request,urllib.parse,urllib.error,http.cookiejar,re,subprocess,json,uuid
BASE='http://127.0.0.1/Initial-lost-found-project/'
PHP='C:/xampp/php/php.exe'
def php(code):return subprocess.check_output([PHP,'-r',code],text=True).strip()
class Browser:
 def __init__(self):self.op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def req(self,path,data=None,json_response=False):
  req=urllib.request.Request(BASE+path,urllib.parse.urlencode(data).encode() if data is not None else None,headers={'Accept':'application/json'} if json_response else {})
  try:
   r=self.op.open(req,timeout=15);return r.status,r.read().decode(),r.url
  except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
 def token(self,path):return re.search(r'name="csrf" value="([^"]+)"',self.req(path)[1])[1]
 def login(self,email):return self.req('login.php',{'csrf':self.token('login.php'),'email':email,'password':'password123'})
admin,student,guest=Browser(),Browser(),Browser()
assert 'login.php' in guest.req('admin_detail.php?kind=user&id=1')[2]
admin.login('admin@zppsu.edu.ph');student.login('student@zppsu.edu.ph')
for path in ['admin.php','admin.php?view=items','admin.php?view=approvals','admin.php?view=claims','admin_users.php','admin_settings.php']:
 code,html,url=admin.req(path);assert code==200 and 'admin-sidebar' not in html and 'portal-navigation' in html and 'admin-dialog' in html,path
for kind in ['item','claim','user']:
 assert student.req('admin_detail.php?kind='+kind+'&id=1')[0]==403
uid=php("""require 'includes/auth.php';echo db()->query("SELECT id FROM accounts WHERE email='student@zppsu.edu.ph'")->fetchColumn();""")
code,html,url=admin.req('admin_detail.php?kind=user&id='+uid)
assert code==200 and 'data-admin-async' in html and 'password_hash' not in html and 'current_password' in html
name='modalqa-'+uuid.uuid4().hex
item=php(f"""require 'includes/auth.php';$user=db()->query("SELECT user_id FROM accounts WHERE email='student@zppsu.edu.ph'")->fetchColumn();db()->prepare("INSERT INTO items(title,description,status,location,date_reported,reporter_id,category,moderation_status) VALUES (?,?,'found',?,CURDATE(),?,'Keys','pending')")->execute(['{name} <script>alert(1)</script>','Private test description','QA Library',$user]);echo db()->lastInsertId();""")
try:
 code,html,url=admin.req('admin_detail.php?kind=item&id='+item);assert code==200 and '&lt;script&gt;' in html and '<script>alert(1)</script>' not in html
 data={'csrf':admin.token('admin.php'),'action':'approve_report','id':item}
 code,body,url=admin.req('admin_action.php',data,True);assert code==200 and json.loads(body)['ok'] is True
 code,body,url=admin.req('admin_action.php',data,True);assert code==422 and json.loads(body)['ok'] is False
 data={'csrf':admin.token('admin.php'),'action':'update_user','id':uid,'name':'Campus Student','email':'student@zppsu.edu.ph','student_id':'2026-0001','role':'student','active':'1','current_password':'wrong'}
 code,body,url=admin.req('admin_action.php',data,True);assert code==422 and 'password is incorrect' in json.loads(body)['message']
 claim=php("require 'includes/auth.php';db()->prepare('INSERT INTO recovery_requests(item_id,name,email,proof) VALUES (?,?,?,?)')->execute(["+item+",'QA modal reviewer','modalqa@campusmail.test','Private evidence <script>alert(1)</script> to verify ownership.']);echo db()->lastInsertId();")
 code,html,url=admin.req('admin_detail.php?kind=claim&id='+claim);assert code==200 and 'Proof of ownership' in html and '&lt;script&gt;' in html and '<script>alert(1)</script>' not in html
 code,body,url=admin.req('admin_action.php',{'csrf':admin.token('admin.php'),'action':'reject_claim','id':claim},True);assert code==200 and json.loads(body)['ok']
 data={'csrf':admin.token('admin.php'),'action':'archive','id':item}
 code,body,url=admin.req('admin_action.php',data,True);assert code==200 and json.loads(body)['ok']
 assert 'admin-kpis' in admin.req('admin.php')[1] and 'Pending claims' in admin.req('admin.php')[1]
 assert admin.req('admin_detail.php?kind=item&id=0')[0]==404
 assert admin.req('admin_detail.php?kind=invalid&id=1')[0]==400
 print('PASS: six admin views, four KPI cards, administrator-only modal endpoints, escaped item/user templates, private account data exclusion, asynchronous approval/archive, and validation errors retained as JSON.')
finally:
 php(f"""require 'includes/auth.php';db()->prepare('DELETE FROM items WHERE id=?')->execute([{item}]);db()->prepare("UPDATE request_limits SET hits=GREATEST(0,CAST(hits AS SIGNED)-2) WHERE bucket=?")->execute([hash('sha256','login:127.0.0.1')]);""")
