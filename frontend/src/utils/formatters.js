import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

export const fmt = {
  currency: (v, currency = 'USD') =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(v ?? 0),

  number: (v) => new Intl.NumberFormat('en-US').format(v ?? 0),

  pct: (v) => v != null ? `${v > 0 ? '+' : ''}${v}%` : '—',

  date: (v, format = 'MMM D, YYYY') => v ? dayjs(v).format(format) : '—',

  dateTime: (v) => v ? dayjs(v).format('MMM D, YYYY h:mm A') : '—',

  relative: (v) => v ? dayjs(v).fromNow() : '—',

  time: (v) => {
    if (!v) return '—'
    const [h, m] = v.split(':')
    const hour = parseInt(h)
    const ampm = hour >= 12 ? 'PM' : 'AM'
    return `${hour % 12 || 12}:${m} ${ampm}`
  },

  hours: (v) => v != null ? `${v}h` : '—',

  initials: (name) =>
    (name || '')
      .split(' ')
      .map(s => s[0])
      .join('')
      .slice(0, 2)
      .toUpperCase(),
}

export const ROLE_LABELS = {
  admin:               'Admin',
  general_manager:     'General Manager',
  assistant_manager:   'Assistant Manager',
  housekeeping_manager:'Housekeeping Manager',
  employee:            'Employee',
}

export const ROLE_COLORS = {
  admin:               'bg-violet-100 text-violet-700',
  general_manager:     'bg-primary-100 text-primary-700',
  assistant_manager:   'bg-amber-100 text-amber-700',
  housekeeping_manager:'bg-rose-100 text-rose-700',
  employee:            'bg-emerald-100 text-emerald-700',
}

export const SHIFT_LABELS = {
  morning:   'Morning',
  afternoon: 'Afternoon',
  evening:   'Evening',
  night:     'Night',
}

export const SHIFT_COLORS = {
  morning:   'bg-amber-100 text-amber-700',
  afternoon: 'bg-sky-100 text-sky-700',
  evening:   'bg-indigo-100 text-indigo-700',
  night:     'bg-slate-100 text-slate-600',
}

export const DEPARTMENT_LABELS = {
  front_desk:    'Front Desk',
  housekeeping:  'Housekeeping',
  maintenance:   'Maintenance',
  food_beverage: 'Food & Beverage',
  management:    'Management',
  other:         'Other',
}

export const TASK_CATEGORY_LABELS = {
  housekeeping:  'Housekeeping',
  maintenance:   'Maintenance',
  front_desk:    'Front Desk',
  food_beverage: 'Food & Beverage',
  other:         'Other',
}

export const TASK_CATEGORY_COLORS = {
  housekeeping:  'bg-rose-100 text-rose-700',
  maintenance:   'bg-amber-100 text-amber-700',
  front_desk:    'bg-sky-100 text-sky-700',
  food_beverage: 'bg-violet-100 text-violet-700',
  other:         'bg-slate-100 text-slate-600',
}

export const PRIORITY_LABELS = {
  low: 'Low', medium: 'Medium', high: 'High', urgent: 'Urgent',
}

export const PRIORITY_COLORS = {
  low:    'bg-slate-100 text-slate-600',
  medium: 'bg-sky-100 text-sky-700',
  high:   'bg-amber-100 text-amber-700',
  urgent: 'bg-red-100 text-red-700',
}

export const STATUS_LABELS = {
  open:        'Open',
  in_progress: 'In Progress',
  completed:   'Completed',
  cancelled:   'Cancelled',
  pending:     'Pending',
  approved:    'Approved',
  flagged:     'Flagged',
}

export const STATUS_COLORS = {
  open:        'bg-slate-100 text-slate-700',
  in_progress: 'bg-sky-100 text-sky-700',
  completed:   'bg-emerald-100 text-emerald-700',
  cancelled:   'bg-slate-100 text-slate-500',
  pending:     'bg-amber-100 text-amber-700',
  approved:    'bg-emerald-100 text-emerald-700',
  flagged:     'bg-red-100 text-red-700',
}
