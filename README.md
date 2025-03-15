# Camagru

A web application that allows users to create and share photos with applied filters/overlays. Users can take pictures using their webcam or upload images, apply various overlays, and share their creations in a public gallery where others can like and comment.

## Features

- **User Management:**
  - User registration with email verification
  - Secure login/logout
  - Password reset functionality
  - Profile management

- **Photo Editor:**
  - Webcam integration
  - Image upload option
  - Selection of overlay filters
  - Server-side image processing

- **Gallery:**
  - Public gallery of all user creations
  - Pagination system
  - Like and comment functionality
  - Email notifications for comments

- **Responsive Design:**
  - Mobile-friendly interface
  - Adaptive layout for different screen sizes

## Technologies Used

- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Backend:** PHP 8.2
- **Database:** MySQL
- **Containerization:** Docker & Docker Compose
- **Web Server:** Nginx

## Requirements

- Docker & Docker Compose
- Web browser (Chrome >= 46 or Firefox >= 41)

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/camagru.git
   cd camagru
   ```

2. Create a `.env` file:
   ```
   cp .env.example .env
   ```

3. Adjust the environment variables in the `.env` file to match your configuration.

4. Start the application:
   ```
   docker-compose up -d
   ```

5. Access the application:
   ```
   http://localhost:8080
   ```

## Directory Structure

```
camagru/
├── config/             # Configuration files
├── controllers/        # Controller classes
├── docker/             # Docker configuration
├── helpers/            # Helper functions
├── models/             # Model classes
├── public/             # Public assets and entry point
│   ├── css/            # CSS files
│   ├── img/            # Images
│   ├── js/             # JavaScript files
│   ├── uploads/        # User uploads (git ignored)
│   └── index.php       # Entry point
├── services/           # Service classes
├── views/              # View templates
├── .env.example        # Example environment file
├── .gitignore          # Git ignore file
├── docker-compose.yml  # Docker Compose config
└── README.md           # Project documentation
```

## Usage

1. Register a new account or log in if you already have one.
2. Go to the Editor page to create photos.
3. Select an overlay from the available options.
4. Either take a picture with your webcam or upload an image.
5. Your processed image will appear in your photos list.
6. Visit the Gallery to view, like, and comment on photos from all users.

## Security Features

- Password hashing
- CSRF protection
- XSS prevention
- Input sanitization
- Secure session management
- SQL injection prevention

## License

[MIT License](LICENSE)

## Acknowledgements

This project was created as part of the web development curriculum.