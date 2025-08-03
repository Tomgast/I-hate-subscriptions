exports.id=563,exports.ids=[563],exports.modules={58359:()=>{},93739:()=>{},95456:(e,t,a)=>{"use strict";a.d(t,{Lz:()=>i});var o=a(53797),r=a(77234),n=a(1926);let i={providers:[(0,r.Z)({clientId:process.env.GOOGLE_CLIENT_ID||"",clientSecret:process.env.GOOGLE_CLIENT_SECRET||""}),(0,o.Z)({name:"credentials",credentials:{email:{label:"Email",type:"email"},password:{label:"Password",type:"password"},name:{label:"Name",type:"text",optional:!0},isSignUp:{label:"Is Sign Up",type:"text",optional:!0}},async authorize(e){if(!e?.email||!e?.password)return null;if("true"===e.isSignUp){let t=(0,n.l)(),{data:a}=await t.from("user_profiles").select("*").eq("email",e.email).single();if(a)throw Error("User already exists");let{data:o,error:r}=await t.auth.admin.createUser({email:e.email,password:e.password,email_confirm:!0,user_metadata:{full_name:e.name||e.email.split("@")[0]}});if(r||!o.user)throw Error(r?.message||"Failed to create user");return{id:o.user.id,email:o.user.email,name:e.name||e.email.split("@")[0],isPaid:!1}}{let t=(0,n.l)(),{data:a}=await t.from("user_profiles").select("*").eq("email",e.email).single();if(!a)throw Error("No user found");let{data:o,error:r}=await t.auth.signInWithPassword({email:e.email,password:e.password});if(r||!o.user)throw Error("Invalid credentials");return{id:a.id,email:a.email,name:a.full_name||a.email.split("@")[0],isPaid:a.has_paid||!1}}}})],callbacks:{async jwt({token:e,user:t,account:a}){if(t&&"isPaid"in t&&(e.isPaid=t.isPaid),a?.provider==="google"&&t){let a=(0,n.l)(),{data:o}=await a.from("user_profiles").select("*").eq("email",t.email).single();if(o)e.isPaid=o.has_paid||!1;else{let{data:o,error:r}=await a.auth.admin.createUser({email:t.email,email_confirm:!0,user_metadata:{full_name:t.name||t.email.split("@")[0]}});!r&&o.user&&(e.isPaid=!1)}}return e},session:async({session:e,token:t})=>(e.user&&(e.user.id=t.sub,e.user.isPaid=t.isPaid),e)},pages:{signIn:"/auth/signin"},session:{strategy:"jwt"}}},77865:(e,t,a)=>{"use strict";a.d(t,{y:()=>n});var o=a(55245);class r{constructor(){this.transporter=null,this.config=this.getEmailConfig(),this.fromEmail=process.env.FROM_EMAIL||"noreply@123cashcontrol.com",this.initializeTransporter()}getEmailConfig(){switch(process.env.EMAIL_PROVIDER||"plesk"){case"plesk":return{provider:"plesk",host:process.env.PLESK_SMTP_HOST||"mail.yourdomain.com",port:parseInt(process.env.PLESK_SMTP_PORT||"587"),secure:"true"===process.env.PLESK_SMTP_SECURE,auth:{user:process.env.PLESK_SMTP_USER||"",pass:process.env.PLESK_SMTP_PASS||""}};case"sendgrid":return{provider:"sendgrid",apiKey:process.env.SENDGRID_API_KEY||""};case"mailgun":return{provider:"mailgun",apiKey:process.env.MAILGUN_API_KEY||""};default:return{provider:"plesk",host:"localhost",port:587,secure:!1,auth:{user:"",pass:""}}}}async initializeTransporter(){try{"plesk"===this.config.provider&&(this.transporter=o.createTransporter({host:this.config.host,port:this.config.port,secure:this.config.secure,auth:this.config.auth,tls:{rejectUnauthorized:!1}}),await this.transporter.verify(),console.log("✅ Plesk SMTP connection verified"))}catch(e){console.error("❌ Email service initialization failed:",e),console.warn("\uD83D\uDCE7 Email notifications will be disabled")}}async sendEmail(e,t,a,o){if(!this.transporter)return console.warn("\uD83D\uDCE7 Email service not available, skipping email to:",e),!1;try{let r={from:`CashControl <${this.fromEmail}>`,to:e,subject:t,html:a,text:o||this.htmlToText(a)},n=await this.transporter.sendMail(r);return console.log("✅ Email sent successfully:",n.messageId),!0}catch(e){return console.error("❌ Failed to send email:",e),!1}}async sendSubscriptionReminder(e,t){let a=this.generateReminderTemplate(t);return this.sendEmail(e,a.subject,a.html,a.text)}async sendWelcomeEmail(e,t){let a=this.generateWelcomeTemplate(t);return this.sendEmail(e,a.subject,a.html,a.text)}async sendUpgradeConfirmation(e,t){let a=this.generateUpgradeTemplate(t);return this.sendEmail(e,a.subject,a.html,a.text)}async sendBankScanComplete(e,t,a){let o=this.generateBankScanTemplate(t,a);return this.sendEmail(e,o.subject,o.html,o.text)}generateReminderTemplate(e){let t=e.daysUntilRenewal<=3?"#ef4444":"#f59e0b",a=e.daysUntilRenewal<=3?"URGENT":"REMINDER",o=`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Subscription Renewal Reminder</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">💳 CashControl</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Subscription Management</p>
      </div>

      <div style="background: ${t}; color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0; font-size: 18px;">${a}: Subscription Renewal</h2>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${e.userName},</h3>
        <p style="margin: 0 0 15px 0;">Your subscription to <strong>${e.subscriptionName}</strong> will renew in <strong>${e.daysUntilRenewal} day${1!==e.daysUntilRenewal?"s":""}</strong>.</p>
        
        <div style="background: white; padding: 20px; border-radius: 6px; border-left: 4px solid #3b82f6;">
          <p style="margin: 0 0 10px 0;"><strong>Amount:</strong> ${e.currency}${e.amount.toFixed(2)}</p>
          <p style="margin: 0 0 10px 0;"><strong>Renewal Date:</strong> ${e.renewalDate}</p>
          <p style="margin: 0;"><strong>Service:</strong> ${e.subscriptionName}</p>
        </div>
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="${e.manageUrl||"https://123cashcontrol.com/dashboard"}" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          Manage Subscription
        </a>
      </div>

      ${e.cancelUrl?`
      <div style="text-align: center; margin-bottom: 25px;">
        <a href="${e.cancelUrl}" 
           style="color: #6b7280; text-decoration: underline; font-size: 14px;">
          Cancel this subscription
        </a>
      </div>
      `:""}

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>This email was sent by CashControl to help you manage your subscriptions.</p>
        <p>Visit <a href="https://123cashcontrol.com" style="color: #3b82f6;">123cashcontrol.com</a> to update your preferences.</p>
      </div>

    </body>
    </html>
    `,r=`
CashControl - Subscription Renewal Reminder

Hi ${e.userName},

Your subscription to ${e.subscriptionName} will renew in ${e.daysUntilRenewal} day${1!==e.daysUntilRenewal?"s":""}.

Details:
- Amount: ${e.currency}${e.amount.toFixed(2)}
- Renewal Date: ${e.renewalDate}
- Service: ${e.subscriptionName}

Manage your subscription: ${e.manageUrl||"https://123cashcontrol.com/dashboard"}
${e.cancelUrl?`Cancel: ${e.cancelUrl}`:""}

---
CashControl - Take control of your subscriptions
https://123cashcontrol.com
    `;return{subject:`${a}: ${e.subscriptionName} renews in ${e.daysUntilRenewal} day${1!==e.daysUntilRenewal?"s":""}`,html:o,text:r}}generateWelcomeTemplate(e){return{subject:"Welcome to CashControl! \uD83C\uDF89",html:`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Welcome to CashControl</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">🎉 Welcome to CashControl!</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Take control of your subscriptions</p>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${e},</h3>
        <p style="margin: 0 0 15px 0;">Welcome to CashControl! We're excited to help you take control of your subscription spending.</p>
        
        <h4 style="color: #1f2937; margin: 20px 0 10px 0;">What you can do:</h4>
        <ul style="margin: 0; padding-left: 20px;">
          <li>Track all your subscriptions in one place</li>
          <li>Get renewal reminders before you're charged</li>
          <li>Scan your European bank accounts for automatic detection</li>
          <li>Export your data anytime</li>
          <li>Enjoy privacy-first design</li>
        </ul>
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="https://123cashcontrol.com/dashboard" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          Get Started
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>Need help? Visit our <a href="https://123cashcontrol.com/help" style="color: #3b82f6;">help center</a> or reply to this email.</p>
      </div>

    </body>
    </html>
    `,text:`
Welcome to CashControl!

Hi ${e},

Welcome to CashControl! We're excited to help you take control of your subscription spending.

What you can do:
- Track all your subscriptions in one place
- Get renewal reminders before you're charged
- Scan your European bank accounts for automatic detection
- Export your data anytime
- Enjoy privacy-first design

Get started: https://123cashcontrol.com/dashboard

Need help? Visit https://123cashcontrol.com/help

---
CashControl Team
https://123cashcontrol.com
    `}}generateUpgradeTemplate(e){return{subject:"⭐ Welcome to CashControl Pro!",html:`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Welcome to CashControl Pro</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">⭐ Welcome to CashControl Pro!</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Unlock the full power of subscription management</p>
      </div>

      <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 25px; border-radius: 8px; text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0 0 10px 0;">🚀 You're now Pro!</h2>
        <p style="margin: 0; opacity: 0.9;">Thank you for upgrading to CashControl Pro</p>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${e},</h3>
        <p style="margin: 0 0 15px 0;">Your upgrade to CashControl Pro is now active! Here's what you can now do:</p>
        
        <h4 style="color: #1f2937; margin: 20px 0 10px 0;">Pro Features Unlocked:</h4>
        <ul style="margin: 0; padding-left: 20px;">
          <li><strong>🏦 European Bank Scanning</strong> - Connect 100+ European banks</li>
          <li><strong>📧 Email Renewal Alerts</strong> - Never miss a renewal again</li>
          <li><strong>📊 Advanced Analytics</strong> - Deep insights into your spending</li>
          <li><strong>📤 Enhanced Export</strong> - PDF reports and advanced formats</li>
          <li><strong>🔄 Bulk Management</strong> - Manage multiple subscriptions at once</li>
          <li><strong>⚡ Priority Support</strong> - Get help when you need it</li>
        </ul>
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="https://123cashcontrol.com/dashboard" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          Explore Pro Features
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>Questions about Pro features? We're here to help!</p>
        <p>Email us at <a href="mailto:support@123cashcontrol.com" style="color: #3b82f6;">support@123cashcontrol.com</a></p>
      </div>

    </body>
    </html>
    `,text:`
Welcome to CashControl Pro!

Hi ${e},

Your upgrade to CashControl Pro is now active! Here's what you can now do:

Pro Features Unlocked:
- 🏦 European Bank Scanning - Connect 100+ European banks
- 📧 Email Renewal Alerts - Never miss a renewal again
- 📊 Advanced Analytics - Deep insights into your spending
- 📤 Enhanced Export - PDF reports and advanced formats
- 🔄 Bulk Management - Manage multiple subscriptions at once
- ⚡ Priority Support - Get help when you need it

Explore Pro features: https://123cashcontrol.com/dashboard

Questions? Email us at support@123cashcontrol.com

---
CashControl Team
https://123cashcontrol.com
    `}}generateBankScanTemplate(e,t){let a=`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Bank Scan Complete</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">🏦 Bank Scan Complete</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">CashControl found your subscriptions</p>
      </div>

      <div style="background: #10b981; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0 0 10px 0;">✅ Scan Successful!</h2>
        <p style="margin: 0; font-size: 18px;">Found ${t} subscription${1!==t?"s":""}</p>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${e},</h3>
        <p style="margin: 0 0 15px 0;">Great news! We've completed scanning your bank account and found ${t} subscription${1!==t?"s":""} that ${1!==t?"were":"was"} automatically detected.</p>
        
        ${t>0?`
        <p style="margin: 0 0 15px 0;">These subscriptions have been added to your CashControl dashboard where you can:</p>
        <ul style="margin: 0; padding-left: 20px;">
          <li>Review and edit subscription details</li>
          <li>Set up renewal reminders</li>
          <li>Track your spending patterns</li>
          <li>Cancel unwanted subscriptions</li>
        </ul>
        `:`
        <p style="margin: 0;">No subscriptions were detected in your recent transactions. You can still manually add any subscriptions you want to track.</p>
        `}
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="https://123cashcontrol.com/dashboard" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          View Your Subscriptions
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>Your bank data is processed securely and never stored permanently.</p>
        <p>Questions? Contact us at <a href="mailto:support@123cashcontrol.com" style="color: #3b82f6;">support@123cashcontrol.com</a></p>
      </div>

    </body>
    </html>
    `,o=`
Bank Scan Complete - CashControl

Hi ${e},

Great news! We've completed scanning your bank account and found ${t} subscription${1!==t?"s":""} that ${1!==t?"were":"was"} automatically detected.

${t>0?`
These subscriptions have been added to your CashControl dashboard where you can:
- Review and edit subscription details
- Set up renewal reminders
- Track your spending patterns
- Cancel unwanted subscriptions
`:`
No subscriptions were detected in your recent transactions. You can still manually add any subscriptions you want to track.
`}

View your subscriptions: https://123cashcontrol.com/dashboard

Your bank data is processed securely and never stored permanently.

Questions? Contact us at support@123cashcontrol.com

---
CashControl Team
https://123cashcontrol.com
    `;return{subject:`🏦 Bank scan complete - Found ${t} subscription${1!==t?"s":""}`,html:a,text:o}}htmlToText(e){return e.replace(/<[^>]*>/g,"").replace(/&nbsp;/g," ").replace(/&amp;/g,"&").replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/\s+/g," ").trim()}}let n=new r},1926:(e,t,a)=>{"use strict";a.d(t,{l:()=>r});var o=a(72438);let r=()=>(0,o.eI)("https://kumslhaqyummcgytyvxv.supabase.co",process.env.SUPABASE_SERVICE_ROLE_KEY,{auth:{autoRefreshToken:!1,persistSession:!1}})}};