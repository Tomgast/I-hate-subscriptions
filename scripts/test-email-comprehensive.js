// Comprehensive Email Test Script
// Tests multiple SMTP configurations for Plesk

import nodemailer from 'nodemailer'
import dotenv from 'dotenv'
import path from 'path'
import { fileURLToPath } from 'url'

// Load environment variables from .env.local
const __dirname = path.dirname(fileURLToPath(import.meta.url))
dotenv.config({ path: path.resolve(__dirname, '../.env.local') })

async function testSMTPConfiguration(config, configName) {
  console.log(`\n🧪 Testing ${configName}...`)
  console.log(`📍 Host: ${config.host}:${config.port}`)
  console.log(`🔒 Secure: ${config.secure}`)
  console.log(`👤 User: ${config.auth.user}`)
  
  try {
    const transporter = nodemailer.createTransport(config)
    
    // Test connection
    const isConnected = await transporter.verify()
    
    if (isConnected) {
      console.log(`✅ ${configName} - Connection successful!`)
      return { success: true, transporter, config }
    } else {
      console.log(`❌ ${configName} - Connection failed`)
      return { success: false, error: 'Connection verification failed' }
    }
    
  } catch (error) {
    console.log(`❌ ${configName} - Error: ${error.message}`)
    return { success: false, error: error.message }
  }
}

async function testEmailConfigurations() {
  console.log('📧 Comprehensive Plesk SMTP Configuration Test')
  console.log('=' .repeat(50))
  
  const baseAuth = {
    user: process.env.PLESK_SMTP_USER,
    pass: process.env.PLESK_SMTP_PASS
  }
  
  // Test multiple configurations
  const configurations = [
    {
      name: 'Current Config (Port 587, STARTTLS)',
      config: {
        host: process.env.PLESK_SMTP_HOST,
        port: 587,
        secure: false,
        auth: baseAuth,
        tls: {
          rejectUnauthorized: false
        }
      }
    },
    {
      name: 'Alternative 1 (Port 465, SSL)',
      config: {
        host: process.env.PLESK_SMTP_HOST,
        port: 465,
        secure: true,
        auth: baseAuth,
        tls: {
          rejectUnauthorized: false
        }
      }
    },
    {
      name: 'Alternative 2 (Port 25, No encryption)',
      config: {
        host: process.env.PLESK_SMTP_HOST,
        port: 25,
        secure: false,
        auth: baseAuth,
        tls: {
          rejectUnauthorized: false
        }
      }
    },
    {
      name: 'Alternative 3 (Port 587, Force TLS)',
      config: {
        host: process.env.PLESK_SMTP_HOST,
        port: 587,
        secure: false,
        auth: baseAuth,
        requireTLS: true,
        tls: {
          rejectUnauthorized: false
        }
      }
    }
  ]
  
  const results = []
  
  for (const { name, config } of configurations) {
    const result = await testSMTPConfiguration(config, name)
    results.push({ name, ...result })
  }
  
  console.log('\n📊 Test Results Summary:')
  console.log('=' .repeat(50))
  
  const successfulConfigs = results.filter(r => r.success)
  const failedConfigs = results.filter(r => !r.success)
  
  if (successfulConfigs.length > 0) {
    console.log('✅ Working configurations:')
    successfulConfigs.forEach(config => {
      console.log(`   - ${config.name}`)
    })
    
    // Try sending a test email with the first working config
    const workingConfig = successfulConfigs[0]
    console.log(`\n📤 Attempting to send test email using: ${workingConfig.name}`)
    
    try {
      const testEmail = {
        from: `CashControl Test <${process.env.FROM_EMAIL}>`,
        to: process.env.PLESK_SMTP_USER,
        subject: '🧪 CashControl SMTP Test - ' + new Date().toISOString(),
        text: `
CashControl SMTP Test

✅ Email configuration working!
Configuration: ${workingConfig.name}
Host: ${workingConfig.config.host}:${workingConfig.config.port}
Secure: ${workingConfig.config.secure}

Test sent at: ${new Date().toLocaleString()}
        `,
        html: `
<!DOCTYPE html>
<html>
<head><title>SMTP Test</title></head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
  <h2>🧪 CashControl SMTP Test</h2>
  <p>✅ <strong>Email configuration working!</strong></p>
  <ul>
    <li><strong>Configuration:</strong> ${workingConfig.name}</li>
    <li><strong>Host:</strong> ${workingConfig.config.host}:${workingConfig.config.port}</li>
    <li><strong>Secure:</strong> ${workingConfig.config.secure}</li>
  </ul>
  <p><em>Test sent at: ${new Date().toLocaleString()}</em></p>
</body>
</html>
        `
      }
      
      const result = await workingConfig.transporter.sendMail(testEmail)
      console.log('✅ Test email sent successfully!')
      console.log('📧 Message ID:', result.messageId)
      console.log('📬 Check your inbox:', process.env.PLESK_SMTP_USER)
      
    } catch (emailError) {
      console.error('❌ Failed to send test email:', emailError.message)
    }
    
  } else {
    console.log('❌ No working configurations found')
  }
  
  if (failedConfigs.length > 0) {
    console.log('\n❌ Failed configurations:')
    failedConfigs.forEach(config => {
      console.log(`   - ${config.name}: ${config.error}`)
    })
  }
  
  console.log('\n🔧 Troubleshooting Recommendations:')
  
  if (results.every(r => r.error && r.error.includes('authentication failed'))) {
    console.log('🔑 All configurations failed with authentication errors.')
    console.log('   → Check your email password in Plesk control panel')
    console.log('   → Verify the email account exists and is active')
    console.log('   → Try resetting the password for info@123cashcontrol.com')
  } else if (results.every(r => r.error && r.error.includes('ECONNREFUSED'))) {
    console.log('🌐 All configurations failed with connection errors.')
    console.log('   → Check if the SMTP server is running')
    console.log('   → Verify firewall settings')
    console.log('   → Check if SMTP is enabled in Plesk')
  } else {
    console.log('📋 Mixed results - some configurations may work better than others.')
  }
}

// Run the comprehensive test
testEmailConfigurations()
