# Admin Dashboard - Complete Guide

## 📋 Overview

A comprehensive admin dashboard has been built for managing the Journalism Workshop Booking System. The admin dashboard provides full CRUD operations for workshops, user management, registration tracking, and PDF export capabilities.

---
// http://localhost:3000/admin/workshops 

## 🏗️ Folder Structure

```
frontend/
├── app/
│   ├── admin/                    # Admin dashboard folder
│   │   ├── layout.tsx            # Admin layout with sidebar & topbar
│   │   ├── page.tsx              # Dashboard home with stats
│   │   ├── login/
│   │   │   └── page.tsx          # Admin login page
│   │   ├── workshops/
│   │   │   ├── page.tsx          # List all workshops
│   │   │   ├── create/
│   │   │   │   └── page.tsx      # Create new workshop
│   │   │   └── edit/
│   │   │       └── [id]/
│   │   │           └── page.tsx  # Edit workshop
│   │   ├── users/
│   │   │   └── page.tsx          # User management
│   │   ├── registrations/
│   │   │   └── page.tsx          # Workshop registrations
│   │   └── components/
│   │       ├── AdminSidebar.tsx  # Sidebar navigation
│   │       └── AdminTopbar.tsx   # Top navigation bar
│   └── ... (user pages)

backend/
├── models/
│   └── Admin.js                  # Admin user model
├── routes/
│   └── adminRoutes.js            # Admin API routes
└── server.js                     # Updated with admin routes
```

---

## 🔐 Admin Authentication

### Default Admin Credentials
- **Email:** `admin@example.com`
- **Password:** `admin123`

### Authentication Flow
1. Admin logs in at `/admin/login`
2. Credentials are verified against Admin model
3. Token stored in localStorage (`adminToken`)
4. Admin layout checks authentication on every page
5. Unauthenticated users redirected to login

### Security Notes
- **Current Implementation:** Simple token-based auth (demo)
- **Production Recommendation:** Use JWT tokens with refresh tokens
- **Password Storage:** Currently plain text (use bcrypt in production)

---

## 📊 Admin Features

### 1. Dashboard Overview (`/admin`)
- **Stats Cards:**
  - Total Workshops
  - Upcoming Workshops (isActive = true)
  - Total Users
  - Total Registrations
- **Quick Actions:**
  - Create Workshop
  - View Users
  - View Registrations

### 2. Workshop Management (`/admin/workshops`)

#### List Workshops
- View all workshops in table format
- Search by title, instructor, location
- Filter by status (Active/Inactive)
- Actions: Edit, Delete

#### Create Workshop (`/admin/workshops/create`)
- **Fields:**
  - Title (required)
  - Description (required)
  - Date (required)
  - Time (required)
  - Location (required)
  - Instructor (required)
  - Max Participants (required)
  - Category (dropdown)
  - Image URL (optional)
  - Active Status (checkbox)

#### Edit Workshop (`/admin/workshops/edit/[id]`)
- Same form as create, pre-filled with existing data
- Updates workshop in database
- Active workshops automatically show on user side

#### Delete Workshop
- Confirmation dialog before deletion
- Removes workshop from database
- Removes workshop from all users' registered workshops

### 3. User Management (`/admin/users`)
- **View All Users:**
  - Name, Email, Phone, NIC/ID
  - Workplace
  - Number of registered workshops
  - Registration date
- **Search:** By name, email, phone, or NIC
- **Export PDF:** Download all user details as PDF

### 4. Workshop Registrations (`/admin/registrations`)
- **Workshop-wise View:**
  - Grouped by workshop
  - Shows all users registered for each workshop
- **User Details per Workshop:**
  - Name, Email, Phone, NIC/ID
  - Workplace
  - Registration date
- **Filters:**
  - Search by user name, email, or workshop title
  - Filter by specific workshop
- **Export PDF:**
  - All registrations or workshop-specific

---

## 🔌 Backend API Endpoints

### Admin Authentication
```
POST /api/admin/login
Body: { email, password }
Response: { success, token, admin }
```

### Dashboard Stats
```
GET /api/admin/stats
Headers: Authorization: Bearer <token>
Response: { totalWorkshops, upcomingWorkshops, totalUsers, totalRegistrations }
```

### Workshop Management
```
GET    /api/admin/workshops          # List all workshops
POST   /api/admin/workshops          # Create workshop
PUT    /api/admin/workshops/:id      # Update workshop
DELETE /api/admin/workshops/:id     # Delete workshop
```

### User Management
```
GET /api/admin/users                 # List all users
```

### Registrations
```
GET /api/admin/registrations         # Get all registrations
```

### PDF Export
```
GET /api/admin/export/users                    # Export users PDF
GET /api/admin/export/registrations            # Export all registrations PDF
GET /api/admin/export/registrations/:workshopId # Export workshop-specific PDF
```

---

## 🗄️ Database Schema

### Admin Model
```javascript
{
  email: String (unique, required),
  password: String (required, min 6 chars),
  name: String (required),
  timestamps: true
}
```

### Workshop Model (Updated)
```javascript
{
  title: String (required),
  description: String (required),
  date: String (required),
  time: String (required),
  location: String (required),
  instructor: String (required),
  maxParticipants: Number (default: 50),
  registeredCount: Number (default: 0),
  isActive: Boolean (default: true),
  category: String (default: "General"),
  image: String (optional),
  timestamps: true
}
```

### User Model (Existing)
- Already includes `registeredWorkshops` array
- Links to Workshop via ObjectId references

---

## 🎨 UI/UX Features

### Design Elements
- **Modern Dashboard Style:** Clean, professional admin interface
- **Responsive Design:** Works on mobile, tablet, desktop
- **Color Scheme:**
  - Blue: Primary actions, active states
  - Green: Success, exports
  - Red: Delete actions, errors
  - Gray: Secondary elements

### Components
- **Sidebar Navigation:** Fixed sidebar with menu items
- **Top Bar:** Admin name and logout button
- **Stats Cards:** Color-coded dashboard cards
- **Data Tables:** Sortable, searchable tables
- **Forms:** Clean form layouts with validation
- **Modals/Confirmation:** Delete confirmations

### Responsive Breakpoints
- **Mobile:** < 768px (sidebar hidden, full-width content)
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px (sidebar visible)

---

## 🔗 How Admin & User Sides Connect

### Data Flow

1. **Admin Creates Workshop:**
   ```
   Admin → POST /api/admin/workshops → MongoDB Workshop Collection
   ```

2. **Workshop Appears on User Side:**
   ```
   User → GET /api/workshops?isActive=true → Returns active workshops
   ```

3. **User Registers:**
   ```
   User → POST /api/workshops/register → Updates User.registeredWorkshops
   ```

4. **Admin Views Registrations:**
   ```
   Admin → GET /api/admin/registrations → Aggregates User + Workshop data
   ```

### Key Connection Points

- **Workshop Status (`isActive`):**
  - Admin sets `isActive: true` → Workshop shows on user side
  - Admin sets `isActive: false` → Workshop hidden from users
  - User-side filters: `GET /api/workshops?isActive=true`

- **Registration Tracking:**
  - User registration adds Workshop ObjectId to `User.registeredWorkshops[]`
  - Admin queries populate this array to show workshop details
  - Admin can see which users registered for which workshops

- **Real-time Updates:**
  - When admin creates/updates workshop, it's immediately available to users
  - When admin deletes workshop, it's removed from user registrations

---

## 📦 Installation & Setup

### Backend Dependencies
```bash
cd backend
npm install pdfkit
```

### Environment Variables
Ensure `.env` file has:
```
MONGO_URI=your_mongodb_connection_string
PORT=5000
```

### Running the Application

1. **Start Backend:**
   ```bash
   cd backend
   npm run dev
   ```

2. **Start Frontend:**
   ```bash
   cd frontend
   npm run dev
   ```

3. **Access Admin Dashboard:**
   - Navigate to: `http://localhost:3000/admin/login`
   - Login with: `admin@example.com` / `admin123`

---

## 🚀 Usage Guide

### Creating a Workshop
1. Login to admin dashboard
2. Navigate to "Workshops" → "Create Workshop"
3. Fill in all required fields
4. Set "Active" checkbox to show on user side
5. Click "Create Workshop"

### Managing Users
1. Navigate to "Users"
2. Use search bar to find specific users
3. View user details and registered workshops
4. Click "Export PDF" to download user list

### Viewing Registrations
1. Navigate to "Registrations"
2. View workshops grouped by title
3. See all users registered for each workshop
4. Filter by workshop or search by user name/email
5. Export PDF for specific workshop or all registrations

### Editing/Deleting Workshops
1. Navigate to "Workshops"
2. Click "Edit" to modify workshop details
3. Click "Delete" to remove workshop (with confirmation)
4. Deleted workshops are automatically removed from user registrations

---

## 🔒 Permissions & Access Control

### Admin-Only Features
- ✅ Create/Edit/Delete workshops
- ✅ View all users
- ✅ View all registrations
- ✅ Export PDFs
- ✅ Access admin dashboard

### User Features (Unaffected)
- ✅ View upcoming workshops (isActive = true)
- ✅ Register for workshops
- ✅ View own profile
- ✅ Cancel registrations
- ❌ Cannot access admin routes

### Route Protection
- Admin routes protected by `authenticateAdmin` middleware
- Frontend admin layout checks localStorage token
- Unauthenticated requests return 401 Unauthorized

---

## 📝 Best Practices Implemented

1. **Separation of Concerns:**
   - Admin routes separate from user routes
   - Admin components in dedicated folder
   - Admin API endpoints prefixed with `/api/admin`

2. **Error Handling:**
   - Try-catch blocks in all async operations
   - User-friendly error messages
   - Proper HTTP status codes

3. **Data Validation:**
   - Required field validation
   - ObjectId validation before database queries
   - Email format validation

4. **Code Organization:**
   - Reusable components (Sidebar, Topbar)
   - Centralized API service (`adminApi.js`)
   - Consistent naming conventions

5. **User Experience:**
   - Loading states
   - Confirmation dialogs for destructive actions
   - Search and filter functionality
   - Responsive design

---

## 🐛 Troubleshooting

### Admin Login Not Working
- Check if Admin model exists in database
- Verify email/password match
- Check browser console for errors
- Ensure backend is running

### Workshops Not Showing on User Side
- Verify `isActive: true` in database
- Check user-side API call: `GET /api/workshops?isActive=true`
- Ensure workshop date is in correct format

### PDF Export Failing
- Ensure `pdfkit` is installed: `npm install pdfkit`
- Check backend logs for errors
- Verify user has registrations before exporting

### Sidebar Not Visible
- Check screen size (sidebar hidden on mobile)
- Verify `AdminSidebar` component is imported
- Check browser console for errors

---

## 🔮 Future Enhancements

### Recommended Improvements
1. **JWT Authentication:** Replace simple token with JWT
2. **Password Hashing:** Use bcrypt for password storage
3. **Role-Based Access:** Multiple admin roles (super admin, editor)
4. **Activity Logs:** Track admin actions
5. **Email Notifications:** Notify users when workshop is created/updated
6. **Bulk Operations:** Select multiple workshops/users for bulk actions
7. **Advanced Filters:** Date range, category filters
8. **Dashboard Charts:** Visual statistics with charts
9. **Export Formats:** CSV, Excel export options
10. **Workshop Analytics:** Registration trends, popular workshops

---

## 📞 Support

For issues or questions:
1. Check this documentation
2. Review code comments
3. Check browser console for errors
4. Verify backend logs

---

## ✅ Checklist

- [x] Admin folder structure created
- [x] Admin layout with sidebar and topbar
- [x] Admin login page
- [x] Dashboard with stats cards
- [x] Workshop CRUD (Create, Read, Update, Delete)
- [x] User management page
- [x] Registration details page
- [x] PDF export functionality
- [x] Backend admin routes
- [x] Admin authentication
- [x] Responsive design
- [x] Search and filter features
- [x] Error handling
- [x] Documentation

---

**Admin Dashboard is fully functional and ready to use!** 🎉
