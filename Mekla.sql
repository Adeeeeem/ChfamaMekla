-- PostgreSQL Schema for Tunisian Restaurant Discovery Webapp "Chfama Mekla?"
-- Database: Mekla

-- Drop existing objects if they exist (in correct order for foreign keys)
DROP TRIGGER IF EXISTS update_restaurant_rating ON reviews;
DROP FUNCTION IF EXISTS update_restaurant_average_rating();
DROP TABLE IF EXISTS restaurant_categories_junction;
DROP TABLE IF EXISTS restaurant_dietary_junction;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS restaurant_dietary_options;
DROP TABLE IF EXISTS restaurant_categories;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS places;
DROP TABLE IF EXISTS gouvernorats;
DROP TABLE IF EXISTS users;

-- Create tables

-- Governorates table (Tunisian regions)
CREATE TABLE gouvernorats (
    id SERIAL PRIMARY KEY,
    name_ar VARCHAR(100) NOT NULL,
    name_fr VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    name_tn VARCHAR(100) NOT NULL
);

-- Places table (cities/areas within governorates)
CREATE TABLE places (
    id SERIAL PRIMARY KEY,
    name_ar VARCHAR(100) NOT NULL,
    name_fr VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    name_tn VARCHAR(100) NOT NULL,
    gouvernorat_id INTEGER NOT NULL,
    CONSTRAINT fk_places_gouvernorat
        FOREIGN KEY (gouvernorat_id)
        REFERENCES gouvernorats(id)
        ON DELETE RESTRICT
);

-- Restaurants table
CREATE TABLE restaurants (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    place_id INTEGER NOT NULL,
    address VARCHAR(500),
    average_rating NUMERIC(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_restaurants_place
        FOREIGN KEY (place_id)
        REFERENCES places(id)
        ON DELETE RESTRICT
);

-- Restaurant categories ( burger, pizza, spicy, sweet, traditional, etc.)
CREATE TABLE restaurant_categories (
    id SERIAL PRIMARY KEY,
    category_key VARCHAR(50) NOT NULL UNIQUE,
    CONSTRAINT chk_category_key
        CHECK (category_key IN ('burger', 'pizza', 'spicy', 'sweet', 'traditional', 'sandwich', 'kebab', 'seafood', 'dessert', 'drink'))
);

-- Restaurant dietary options (gluten_free, dairy_free, sugar_free, nut_free)
CREATE TABLE restaurant_dietary_options (
    id SERIAL PRIMARY KEY,
    dietary_key VARCHAR(50) NOT NULL UNIQUE,
    CONSTRAINT chk_dietary_key
        CHECK (dietary_key IN ('gluten_free', 'dairy_free', 'sugar_free', 'nut_free', 'vegan', 'vegetarian'))
);

-- Junction table for restaurants and categories (many-to-many)
CREATE TABLE restaurant_categories_junction (
    restaurant_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    PRIMARY KEY (restaurant_id, category_id),
    CONSTRAINT fk_rcj_restaurant
        FOREIGN KEY (restaurant_id)
        REFERENCES restaurants(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_rcj_category
        FOREIGN KEY (category_id)
        REFERENCES restaurant_categories(id)
        ON DELETE CASCADE
);

-- Junction table for restaurants and dietary options (many-to-many)
CREATE TABLE restaurant_dietary_junction (
    restaurant_id INTEGER NOT NULL,
    dietary_id INTEGER NOT NULL,
    PRIMARY KEY (restaurant_id, dietary_id),
    CONSTRAINT fk_rdj_restaurant
        FOREIGN KEY (restaurant_id)
        REFERENCES restaurants(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_rdj_dietary
        FOREIGN KEY (dietary_id)
        REFERENCES restaurant_dietary_options(id)
        ON DELETE CASCADE
);

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews table
CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    restaurant_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_restaurant
        FOREIGN KEY (restaurant_id)
        REFERENCES restaurants(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT uq_restaurant_user_review
        UNIQUE (restaurant_id, user_id)
);

-- Create indexes on foreign key columns for better query performance
CREATE INDEX idx_places_gouvernorat_id ON places(gouvernorat_id);
CREATE INDEX idx_restaurants_place_id ON restaurants(place_id);
CREATE INDEX idx_restaurant_categories_junction_restaurant_id ON restaurant_categories_junction(restaurant_id);
CREATE INDEX idx_restaurant_categories_junction_category_id ON restaurant_categories_junction(category_id);
CREATE INDEX idx_restaurant_dietary_junction_restaurant_id ON restaurant_dietary_junction(restaurant_id);
CREATE INDEX idx_restaurant_dietary_junction_dietary_id ON restaurant_dietary_junction(dietary_id);
CREATE INDEX idx_reviews_restaurant_id ON reviews(restaurant_id);
CREATE INDEX idx_reviews_user_id ON reviews(user_id);

-- Function to update restaurant's average_rating when reviews change
CREATE OR REPLACE FUNCTION update_restaurant_average_rating()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE restaurants
    SET average_rating = (
        SELECT COALESCE(ROUND(AVG(rating)::numeric, 2), 0.00)
        FROM reviews
        WHERE restaurant_id = COALESCE(NEW.restaurant_id, OLD.restaurant_id)
    )
    WHERE id = COALESCE(NEW.restaurant_id, OLD.restaurant_id);

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to call the function on review insert, update, or delete
CREATE TRIGGER update_restaurant_rating
AFTER INSERT OR UPDATE OR DELETE ON reviews
FOR EACH ROW
EXECUTE FUNCTION update_restaurant_average_rating();

-- Insert initial data: Tunisian governorates
INSERT INTO gouvernorats (name_ar, name_fr, name_en, name_tn) VALUES
('تونس', 'Tunis', 'Tunis', 'Tunis'),
('أريانة', 'Ariana', 'Ariana', 'Ariana'),
('بن عروس', 'Ben Arous', 'Ben Arous', 'Ben Arous'),
('منوبة', 'Manouba', 'Manouba', 'Manouba'),
('نابل', 'Nabeul', 'Nabeul', 'Nabeul'),
('زغوان', 'Zaghouan', 'Zaghouan', 'Zaghouan'),
('بسكرة', 'Bizerte', 'Bizerte', 'Bizerte'),
('باجة', 'Béja', 'Béja', 'Beja'),
('جندوبة', 'Jendouba', 'Jendouba', 'Jendouba'),
('الكاف', 'Kef', 'Kef', 'El Kef'),
('سليانة', 'Siliana', 'Siliana', 'Siliana'),
('القيروان', 'Kairouan', 'Kairouan', 'Kairouan'),
('القصرين', 'Kasserine', 'Kasserine', 'Kasserine'),
('سيدي بوزيد', 'Sidi Bouzid', 'Sidi Bouzid', 'Sidi Bouzid'),
('المهدية', 'Mahdia', 'Mahdia', 'Mahdia'),
('صفاقس', 'Sfax', 'Sfax', 'Sfax'),
('قفص', 'Gafsa', 'Gafsa', 'Gafsa'),
('توزر', 'Tozeur', 'Tozeur', 'Tozeur'),
('قبلي', 'Kebili', 'Kebili', 'Kebili'),
('مدنين', 'Medenine', 'Medenine', 'Medenine'),
('تطاوين', 'Tataouine', 'Tataouine', 'Tataouine'),
('قفصى', 'Gabès', 'Gabès', 'Gabes');

-- Insert initial categories
INSERT INTO restaurant_categories (category_key) VALUES
('burger'), ('pizza'), ('spicy'), ('sweet'), ('traditional'),
('sandwich'), ('kebab'), ('seafood'), ('dessert'), ('drink');

-- Insert initial dietary options
INSERT INTO restaurant_dietary_options (dietary_key) VALUES
('gluten_free'), ('dairy_free'), ('sugar_free'), ('nut_free'),
('vegan'), ('vegetarian');