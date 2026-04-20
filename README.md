# Chfama Mekla?

Tunisian Restaurant Discovery Webapp - Find the best places to eat in Tunisia!

## Description

**Chfama Mekla?** is a web application for discovering and reviewing restaurants across Tunisia. Users can explore restaurants by governorate, browse by category, filter by dietary options, and leave reviews with ratings.

## Features

- Browse restaurants by governorate and city (including Grand Tunis)
- Search and filter by food categories (burger, pizza, traditional, kebab, etc.)
- Filter by dietary options (gluten-free, vegan, vegetarian, etc.)
- User reviews and ratings system
- Responsive design for mobile and desktop

## Tech Stack

- **Backend**: Node.js / Express / PostgreSQL
- **Frontend**: React / Next.js (to be determined)
- **Database**: PostgreSQL 13+

## Getting Started

### Prerequisites

- PostgreSQL 13 or higher
- Node.js 18+

### Database Setup

1. Create the database:
```sql
CREATE DATABASE Mekla;
```

2. Run the schema:
```bash
psql -U postgres -d Mekla -f database/Mekla.sql
```

### Environment Variables

Create a `.env` file:
```
DATABASE_URL=postgresql://user:password@localhost:5432/Mekla
```

## Database Schema

### Tables

| Table | Description |
|-------|-------------|
| `gouvernorats` | Tunisian governorates (24 + Grand Tunis) |
| `places` | Cities/areas within governorates |
| `restaurants` | Restaurant listings |
| `restaurant_categories` | Food categories |
| `restaurant_dietary_options` | Dietary options |
| `restaurant_categories_junction` | Restaurant-Category relationship |
| `restaurant_dietary_junction` | Restaurant-Dietary relationship |
| `users` | Application users |
| `reviews` | User reviews and ratings |

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

MIT License
