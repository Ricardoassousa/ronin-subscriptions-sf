## Features

The Subscription Management application implements the core and high-impact features using Symfony 6.4.

### User Management
- [x] Create account
- [x] Login / Logout
- [x] Edit profile
- [x] Role management (admin)
- [ ] View subscription history (high-impact, optional)

### Subscription Plan Management
- [x] Create / Edit / Delete subscription plan
- [x] Define pricing and plan features
- [x] Enable / disable plans (optional)

### Subscription Management
- [x] Subscribe to a plan
- [x] Change subscription plan
- [x] Cancel subscription
- [x] Track subscription status (active, cancelled, expired)
- [x] Display next billing date
- [x] Pause / Resume subscription (optional, high-impact)

### Billing and Payments
- [x] Simulate payment processing
- [x] Record payments
- [x] Generate invoices (simulated)
- [x] View payment history (optional, high-impact)
- [x] Download invoices (PDF, optional, high-impact)

### Admin Dashboard
- [x] Dashboard overview
- [x] Display summary widgets with total subscriptions, active vs cancelled, and MRR
- [x] Recent activity feed
- [x] Charts and analytics (optional, high-impact)

### Notifications
- [x] Flash messages
- [x] Subscription confirmation
- [x] Upcoming renewal reminder (optional)
- [x] Payment failure notification (optional, high-impact)

### Permissions & Security
- [x] Admin-only sections
- [x] User roles & permissions
- [x] Route protection using Symfony Security
- [ ] Activity logs / subscription tracking (optional)

### UX/UI
- [ ] Responsive layout
- [ ] Subscription plans comparison table
- [ ] Status indicators for subscriptions
- [ ] Success/error alerts
- [ ] Consistent navigation