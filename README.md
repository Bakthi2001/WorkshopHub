# Journalism Workshop Management Platform

A full-stack web application for managing journalism workshops, built with Next.js (frontend) and Node.js/Express (backend).

## 🚀 Quick Start
// http://localhost:3000/admin/workshops 

### Prerequisites
- Node.js 18+ 
- npm 9+
- MongoDB (Atlas or local)

### Installation

```bash
# Install all dependencies (both frontend and backend)
npm run install:all

# Or install separately
cd backend && npm install
cd ../frontend && npm install
```

### Development

```bash
# Run both frontend and backend concurrently
npm run dev

# Or run separately
npm run dev:backend   # Backend on http://localhost:5000
npm run dev:frontend  # Frontend on http://localhost:3000
```

### Production Build

```bash
# Build frontend for production
npm run build

# Start production servers
npm run start
```

## 📁 Project Structure

```
├── backend/                 # Node.js/Express backend
│   ├── models/             # MongoDB models
│   ├── routes/             # API routes
│   ├── utils/              # Utility functions
│   ├── uploads/            # File uploads directory
│   └── server.js           # Entry point
│
├── frontend/               # Next.js frontend
│   ├── app/               # App router pages
│   │   ├── admin/        # Admin dashboard
│   │   ├── components/   # Reusable components
│   │   ├── login/        # Authentication pages
│   │   └── ...           # Other pages
│   ├── services/         # API service layer
│   └── public/           # Static assets
│
├── package.json           # Root package.json
├── ecosystem.config.js    # PM2 configuration
├── docker-compose.yml     # Docker setup
└── Dockerfile            # Docker multi-stage build
```

## ✅ Build Status

- **Frontend:** ✅ Built successfully
- **Backend:** ✅ Tested successfully  
- **MongoDB:** ✅ Connected successfully
- **Ready for deployment:** ✅ Yes

## 🔧 Environment Variables

### Backend (.env)
```env
PORT=5000
MONGO_URI=your_mongodb_connection_string
BREVO_SMTP_KEY=your_brevo_key
BREVO_SMTP_USER=your_brevo_user
EMAIL_FROM=your_email
```

### Frontend
```env
NEXT_PUBLIC_API_URL=http://localhost:5000/api
```

## 🎯 Features

- **User Management**: Registration, login, profile management
- **Workshop Management**: Create, edit, view, and register for workshops
- **Admin Dashboard**: Manage users, workshops, trainers, and registrations
- **File Uploads**: Image uploads for workshops
- **Email Notifications**: Send emails via Brevo
- **Responsive Design**: Works on desktop, tablet, and mobile
- **TypeScript**: Full type safety in frontend

## 🚢 Deployment Options

See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for detailed deployment instructions.

### Quick Deploy Options:

1. **Vercel (Frontend) + Render (Backend)** - Recommended
2. **Railway** - Full-stack deployment
3. **Heroku** - Traditional PaaS
4. **Docker** - Containerized deployment
5. **VPS** - Full control (DigitalOcean, AWS, etc.)

## 📜 Available Scripts

```bash
npm run install:all      # Install all dependencies
npm run dev              # Run both in development mode
npm run dev:backend      # Run backend only
npm run dev:frontend     # Run frontend only
npm run build            # Build frontend for production
npm run start            # Start both in production mode
npm run start:backend    # Start backend in production
npm run start:frontend   # Start frontend in production
npm run test:backend     # Test backend database connection
```

## 🔐 Default Admin Credentials

⚠️ **Change these in production!**

- **Super Admin:** admin@example.com / admin123
- **Coordinator 1:** coordinator1@example.com / coordinator123
- **Coordinator 2:** coordinator2@example.com / coordinator223

## 🐳 Docker Deployment

```bash
# Build and run with Docker Compose
docker-compose up -d

# View logs
docker-compose logs -f

# Stop containers
docker-compose down
```

## 📊 Tech Stack

### Frontend
- Next.js 16 (App Router)
- React 19
- TypeScript
- Tailwind CSS

### Backend
- Node.js
- Express 5
- MongoDB/Mongoose
- Multer (file uploads)
- Nodemailer (email)

## 🔍 API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/verify` - Email verification

### Workshops
- `GET /api/workshops` - Get all workshops
- `POST /api/workshops` - Create workshop (admin)
- `PUT /api/workshops/:id` - Update workshop (admin)
- `DELETE /api/workshops/:id` - Delete workshop (admin)
- `POST /api/workshops/:id/register` - Register for workshop

### User
- `GET /api/user/profile` - Get user profile
- `PUT /api/user/profile` - Update user profile

### Admin
- `GET /api/admin/users` - Get all users
- `GET /api/admin/registrations` - Get all registrations
- `POST /api/admin/trainers` - Create trainer
- And more...

## 📝 Additional Documentation

- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Comprehensive deployment guide
- [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md) - Pre-launch checklist
- [frontend/README.md](frontend/README.md) - Frontend documentation

## 🤝 Support

For issues or questions:
1. Check the documentation
2. Review error logs
3. Verify environment variables
4. Check database connection

## 📄 License

ISC

---

**Status:** ✅ Production Ready
**Last Build:** Successful
**Version:** 1.0.0
