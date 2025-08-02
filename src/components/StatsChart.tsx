'use client'

import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'

interface StatsChartProps {
  data: Array<{ month: string; amount: number }>
}

export function StatsChart({ data }: StatsChartProps) {
  return (
    <div className="h-64 w-full">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
          <XAxis 
            dataKey="month" 
            className="text-gray-600 dark:text-gray-400"
            fontSize={12}
          />
          <YAxis 
            className="text-gray-600 dark:text-gray-400"
            fontSize={12}
            tickFormatter={(value) => `$${value}`}
          />
          <Tooltip
            contentStyle={{
              backgroundColor: 'var(--tooltip-bg)',
              border: '1px solid var(--tooltip-border)',
              borderRadius: '8px',
              color: 'var(--tooltip-text)'
            }}
            formatter={(value: number) => [`$${value.toFixed(2)}`, 'Monthly Spend']}
          />
          <Line 
            type="monotone" 
            dataKey="amount" 
            stroke="#3b82f6" 
            strokeWidth={2}
            dot={{ fill: '#3b82f6', strokeWidth: 2, r: 4 }}
            activeDot={{ r: 6, stroke: '#3b82f6', strokeWidth: 2 }}
          />
        </LineChart>
      </ResponsiveContainer>
      
      <style jsx global>{`
        :root {
          --tooltip-bg: white;
          --tooltip-border: #e5e7eb;
          --tooltip-text: #374151;
        }
        
        .dark {
          --tooltip-bg: #1f2937;
          --tooltip-border: #4b5563;
          --tooltip-text: #f3f4f6;
        }
      `}</style>
    </div>
  )
}
