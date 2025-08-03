'use client'

import { Subscription, SUBSCRIPTION_CATEGORIES, BILLING_CYCLES } from '@/types/subscription'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

export function exportToCSV(subscriptions: Subscription[]): void {
  const headers = [
    'Name',
    'Category',
    'Price',
    'Currency',
    'Billing Cycle',
    'Monthly Equivalent',
    'Next Billing Date',
    'Status',
    'Description',
    'Website',
    'Tags',
    'Reminder Days',
    'Created Date'
  ]

  const getMonthlyAmount = (subscription: Subscription): number => {
    const cycle = BILLING_CYCLES.find(c => c.value === subscription.billingCycle)
    return subscription.price * (cycle?.multiplier || 1)
  }

  const rows = subscriptions.map(sub => [
    sub.name,
    SUBSCRIPTION_CATEGORIES[sub.category].label,
    sub.price.toFixed(2),
    sub.currency,
    BILLING_CYCLES.find(c => c.value === sub.billingCycle)?.label || sub.billingCycle,
    getMonthlyAmount(sub).toFixed(2),
    new Date(sub.nextBillingDate).toLocaleDateString(),
    sub.isActive ? 'Active' : 'Inactive',
    sub.description || '',
    sub.website || '',
    sub.tags?.join(', ') || '',
    sub.reminderDays.toString(),
    new Date(sub.createdAt).toLocaleDateString()
  ])

  const csvContent = [headers, ...rows]
    .map(row => row.map(cell => `"${cell}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  
  link.setAttribute('href', url)
  link.setAttribute('download', `subscriptions-${new Date().toISOString().split('T')[0]}.csv`)
  link.style.visibility = 'hidden'
  
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

export function exportToPDF(subscriptions: Subscription[]): void {
  const doc = new jsPDF()
  
  // Add title
  doc.setFontSize(20)
  doc.text('Subscription Report', 20, 20)
  
  // Add generation date
  doc.setFontSize(12)
  doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 30)
  
  // Add summary statistics
  const activeSubscriptions = subscriptions.filter(sub => sub.isActive)
  const totalMonthly = activeSubscriptions.reduce((sum, sub) => {
    const cycle = BILLING_CYCLES.find(c => c.value === sub.billingCycle)
    return sum + (sub.price * (cycle?.multiplier || 1))
  }, 0)
  
  doc.text(`Total Active Subscriptions: ${activeSubscriptions.length}`, 20, 40)
  doc.text(`Total Monthly Cost: $${totalMonthly.toFixed(2)}`, 20, 50)
  doc.text(`Total Yearly Cost: $${(totalMonthly * 12).toFixed(2)}`, 20, 60)
  
  // Prepare table data
  const getMonthlyAmount = (subscription: Subscription): number => {
    const cycle = BILLING_CYCLES.find(c => c.value === subscription.billingCycle)
    return subscription.price * (cycle?.multiplier || 1)
  }

  const tableData = subscriptions.map(sub => [
    sub.name,
    SUBSCRIPTION_CATEGORIES[sub.category].label,
    `$${sub.price.toFixed(2)}`,
    BILLING_CYCLES.find(c => c.value === sub.billingCycle)?.label || sub.billingCycle,
    `$${getMonthlyAmount(sub).toFixed(2)}`,
    new Date(sub.nextBillingDate).toLocaleDateString(),
    sub.isActive ? 'Active' : 'Inactive'
  ])

  // Add table
  autoTable(doc, {
    head: [['Name', 'Category', 'Price', 'Billing', 'Monthly', 'Next Billing', 'Status']],
    body: tableData,
    startY: 80,
    styles: {
      fontSize: 8,
      cellPadding: 3,
    },
    headStyles: {
      fillColor: [59, 130, 246], // Primary blue
      textColor: 255,
      fontStyle: 'bold',
    },
    alternateRowStyles: {
      fillColor: [248, 250, 252], // Light gray
    },
    columnStyles: {
      0: { cellWidth: 35 }, // Name
      1: { cellWidth: 25 }, // Category
      2: { cellWidth: 20 }, // Price
      3: { cellWidth: 20 }, // Billing
      4: { cellWidth: 20 }, // Monthly
      5: { cellWidth: 25 }, // Next Billing
      6: { cellWidth: 20 }, // Status
    },
  })

  // Add category breakdown
  const categoryBreakdown: Record<string, number> = {}
  activeSubscriptions.forEach(sub => {
    const categoryLabel = SUBSCRIPTION_CATEGORIES[sub.category].label
    const monthlyAmount = getMonthlyAmount(sub)
    categoryBreakdown[categoryLabel] = (categoryBreakdown[categoryLabel] || 0) + monthlyAmount
  })

  const finalY = (doc as any).lastAutoTable.finalY || 80
  
  if (Object.keys(categoryBreakdown).length > 0) {
    doc.text('Monthly Spending by Category:', 20, finalY + 20)
    
    const categoryData = Object.entries(categoryBreakdown)
      .sort(([, a], [, b]) => b - a)
      .map(([category, amount]) => [category, `$${amount.toFixed(2)}`])

    autoTable(doc, {
      head: [['Category', 'Monthly Amount']],
      body: categoryData,
      startY: finalY + 30,
      styles: {
        fontSize: 10,
        cellPadding: 4,
      },
      headStyles: {
        fillColor: [59, 130, 246],
        textColor: 255,
        fontStyle: 'bold',
      },
      columnStyles: {
        0: { cellWidth: 80 },
        1: { cellWidth: 40 },
      },
    })
  }

  // Save the PDF
  doc.save(`subscriptions-report-${new Date().toISOString().split('T')[0]}.pdf`)
}

export function exportToJSON(subscriptions: Subscription[]): void {
  const exportData = {
    exportDate: new Date().toISOString(),
    subscriptions: subscriptions,
    summary: {
      activeCount: subscriptions.length,
      activeSubscriptions: subscriptions.filter(sub => sub.isActive).length,
      totalMonthlySpend: subscriptions
        .filter(sub => sub.isActive)
        .reduce((sum, sub) => {
          const cycle = BILLING_CYCLES.find(c => c.value === sub.billingCycle)
          return sum + (sub.price * (cycle?.multiplier || 1))
        }, 0)
    }
  }

  const dataStr = JSON.stringify(exportData, null, 2)
  const blob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  
  link.href = url
  link.download = `subscriptions-backup-${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}
