-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 11, 2025 at 06:34 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `SA_PriSchool`
--

-- --------------------------------------------------------

--
-- Table structure for table `book_loan`
--

CREATE TABLE `book_loan` (
  `loan_id` int(11) NOT NULL,
  `pupil_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `book_status` enum('Available','Borrowed','Returned','Overdue') DEFAULT NULL,
  `borrowed_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `returned_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_loan`
--

INSERT INTO `book_loan` (`loan_id`, `pupil_id`, `book_id`, `book_status`, `borrowed_date`, `due_date`, `returned_date`) VALUES
(1, 1, 1, 'Borrowed', '2025-04-01', NULL, NULL),
(2, 2, 2, 'Returned', '2025-03-15', NULL, '2025-03-20'),
(3, 5, 3, 'Borrowed', '2025-04-02', NULL, NULL),
(4, 10, 4, 'Returned', '2025-04-01', NULL, '2025-04-07'),
(5, 22, 5, 'Borrowed', '2025-04-03', NULL, NULL),
(6, 23, 6, 'Returned', '2025-04-01', NULL, '2025-04-10'),
(7, 24, 7, 'Borrowed', '2025-04-05', NULL, NULL),
(8, 25, 8, 'Returned', '2025-04-02', NULL, '2025-04-12'),
(9, 26, 9, 'Returned', '2025-04-06', NULL, '2025-04-10'),
(10, 27, 10, 'Returned', '2025-04-03', NULL, '2025-04-15'),
(11, 28, 11, 'Borrowed', '2025-04-07', NULL, NULL),
(12, 29, 12, 'Returned', '2025-04-05', NULL, '2025-04-17'),
(13, 30, 13, 'Borrowed', '2025-04-08', NULL, NULL),
(14, 31, 14, 'Returned', '2025-04-06', NULL, '2025-04-18'),
(15, 32, 15, 'Borrowed', '2025-04-09', NULL, NULL),
(16, 33, 16, 'Returned', '2025-04-07', NULL, '2025-04-19'),
(17, 34, 17, 'Borrowed', '2025-04-10', NULL, NULL),
(18, 35, 18, 'Returned', '2025-04-08', NULL, NULL),
(19, 36, 19, 'Borrowed', '2025-04-11', NULL, NULL),
(20, 37, 20, 'Returned', '2025-04-09', NULL, '2025-04-20'),
(21, 38, 21, 'Borrowed', '2025-04-12', NULL, NULL),
(22, 3, 1, 'Returned', '2025-04-10', '2025-04-24', '2025-04-11'),
(23, 6, 19, 'Borrowed', '2025-04-11', '2025-04-25', NULL),
(24, 5, 5, 'Borrowed', '2025-04-11', '2025-04-25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(20) DEFAULT NULL,
  `class_capacity` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`class_id`, `class_name`, `class_capacity`, `teacher_id`) VALUES
(1, 'Reception Year', 30, 1),
(2, 'Year 1', 30, 2),
(3, 'Year 2', 30, 3),
(4, 'Year 3', 30, 4),
(5, 'Year 4', 30, 5),
(6, 'Year 5', 30, 6),
(7, 'Year 6', 30, 7);

--
-- Triggers `class`
--
DELIMITER $$
CREATE TRIGGER `log_class_changes` AFTER UPDATE ON `class` FOR EACH ROW BEGIN
    IF OLD.class_name != NEW.class_name THEN
        INSERT INTO class_history (class_id, changed_field, old_value, new_value)
        VALUES (OLD.class_id, 'class_name', OLD.class_name, NEW.class_name);
    END IF;
    
    IF OLD.class_capacity != NEW.class_capacity THEN
        INSERT INTO class_history (class_id, changed_field, old_value, new_value)
        VALUES (OLD.class_id, 'class_capacity', OLD.class_capacity, NEW.class_capacity);
    END IF;
    
    IF OLD.teacher_id != NEW.teacher_id THEN
        INSERT INTO class_history (class_id, changed_field, old_value, new_value)
        VALUES (OLD.class_id, 'teacher_id', OLD.teacher_id, NEW.teacher_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `class_history`
--

CREATE TABLE `class_history` (
  `log_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `changed_field` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `change_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_history`
--

INSERT INTO `class_history` (`log_id`, `class_id`, `changed_field`, `old_value`, `new_value`, `change_date`) VALUES
(1, 1, 'class_capacity', '30', '28', '2025-04-09 13:46:43'),
(2, 1, 'class_capacity', '28', '30', '2025-04-09 13:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `guardian`
--

CREATE TABLE `guardian` (
  `guardian_id` int(11) NOT NULL,
  `g_first_name` varchar(30) DEFAULT NULL,
  `g_last_name` varchar(30) DEFAULT NULL,
  `g_phone` varchar(20) DEFAULT NULL,
  `g_email` varchar(100) DEFAULT NULL,
  `g_address` varchar(255) DEFAULT NULL,
  `g_occupation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guardian`
--

INSERT INTO `guardian` (`guardian_id`, `g_first_name`, `g_last_name`, `g_phone`, `g_email`, `g_address`, `g_occupation`) VALUES
(1, 'John', 'Smith', '1112223333', 'john.smith@example.com', '456 Oak St', 'Engineer'),
(2, 'Emily', 'Brown', '4445556666', 'emily.brown@example.com', '789 Pine St', 'Teacher'),
(3, 'Noah', 'Clark', '07211434993', 'noah.clark@example.com', '471 Random St', 'Chef'),
(4, 'Lucas', 'Brown', '07852027564', 'lucas.brown@example.com', '286 Random St', 'Driver'),
(5, 'Liam', 'Young', '07891615160', 'liam.young@example.com', '135 Random St', 'Engineer'),
(6, 'Amelia', 'Wilson', '07718831440', 'amelia.wilson@example.com', '576 Random St', 'Driver'),
(7, 'Liam', 'Brown', '07555205867', 'liam.brown@example.com', '693 Random St', 'Engineer'),
(8, 'Isla', 'Taylor', '07434026358', 'isla.taylor@example.com', '867 Random St', 'Chef'),
(9, 'Olivia', 'Brown', '07132271032', 'olivia.brown@example.com', '600 Random St', 'Engineer'),
(10, 'Isla', 'Wilson', '07962914104', 'isla.wilson@example.com', '933 Random St', 'Doctor'),
(11, 'James', 'Allen', '07788941026', 'james.allen@example.com', '782 Random St', 'Nurse'),
(12, 'Noah', 'Clark', '07525192997', 'noah.clark@example.com', '940 Random St', 'Doctor'),
(13, 'James', 'Johnson', '07592528912', 'james.johnson@example.com', '354 Random St', 'Doctor'),
(14, 'Liam', 'Smith', '07892289942', 'liam.smith@example.com', '291 Random St', 'Chef'),
(15, 'Emma', 'Smith', '07945719633', 'emma.smith@example.com', '404 Random St', 'Doctor'),
(16, 'Sophia', 'Smith', '07485852018', 'sophia.smith@example.com', '105 Random St', 'Doctor'),
(17, 'Liam', 'Johnson', '07256465992', 'liam.johnson@example.com', '867 Random St', 'Chef'),
(18, 'Olivia', 'Hall', '07422500993', 'olivia.hall@example.com', '223 Random St', 'Driver'),
(19, 'Liam', 'Brown', '07114230837', 'liam.brown@example.com', '837 Random St', 'Nurse'),
(20, 'Emma', 'Johnson', '07228455063', 'emma.johnson@example.com', '806 Random St', 'Nurse'),
(21, 'Liam', 'Wilson', '07445993057', 'liam.wilson@example.com', '300 Random St', 'Engineer'),
(22, 'Liam', 'Taylor', '07337988951', 'liam.taylor@example.com', '998 Random St', 'Driver'),
(23, 'Amelia', 'Hall', '07190356123', 'amelia.hall@example.com', '196 Random St', 'Engineer'),
(24, 'Emma', 'Smith', '07495112824', 'emma.smith@example.com', '704 Random St', 'Chef'),
(25, 'Liam', 'Wilson', '07546087100', 'liam.wilson@example.com', '965 Random St', 'Driver'),
(26, 'Emma', 'Clark', '07473135695', 'emma.clark@example.com', '504 Random St', 'Driver'),
(27, 'James', 'Johnson', '07736750690', 'james.johnson@example.com', '818 Random St', 'Chef'),
(28, 'Isla', 'Young', '07260203310', 'isla.young@example.com', '720 Random St', 'Engineer'),
(29, 'Lucas', 'Brown', '07477924753', 'lucas.brown@example.com', '567 Random St', 'Driver'),
(30, 'Ava', 'Allen', '07197435840', 'ava.allen@example.com', '570 Random St', 'Teacher'),
(31, 'Isla', 'Wilson', '07823076557', 'isla.wilson@example.com', '633 Random St', 'Teacher'),
(32, 'Noah', 'Wilson', '07524359574', 'noah.wilson@example.com', '327 Random St', 'Teacher'),
(33, 'Mia', 'Allen', '07377102833', 'mia.allen@example.com', '959 Random St', 'Engineer'),
(34, 'James', 'Taylor', '07647632458', 'james.taylor@example.com', '679 Random St', 'Teacher'),
(35, 'Liam', 'Wilson', '07548080549', 'liam.wilson@example.com', '975 Random St', 'Driver'),
(36, 'Olivia', 'Taylor', '07582510225', 'olivia.taylor@example.com', '361 Random St', 'Engineer'),
(37, 'Isla', 'Allen', '07631313092', 'isla.allen@example.com', '238 Random St', 'Engineer'),
(38, 'Liam', 'Walker', '07727337783', 'liam.walker@example.com', '593 Random St', 'Engineer'),
(39, 'Lucas', 'Clark', '07120704979', 'lucas.clark@example.com', '449 Random St', 'Engineer'),
(40, 'Lucas', 'Taylor', '07469863415', 'lucas.taylor@example.com', '396 Random St', 'Chef'),
(41, 'Emma', 'Wilson', '07388867022', 'emma.wilson@example.com', '714 Random St', 'Teacher'),
(42, 'Noah', 'Johnson', '07199888213', 'noah.johnson@example.com', '836 Random St', 'Chef'),
(43, 'Isla', 'Young', '07884287683', 'isla.young@example.com', '834 Random St', 'Doctor'),
(44, 'Sophia', 'Hall', '07658381656', 'sophia.hall@example.com', '223 Random St', 'Driver'),
(45, 'Liam', 'Wilson', '07826606173', 'liam.wilson@example.com', '409 Random St', 'Nurse'),
(46, 'Noah', 'Walker', '07283090838', 'noah.walker@example.com', '446 Random St', 'Driver'),
(47, 'Sophia', 'Brown', '07864356518', 'sophia.brown@example.com', '120 Random St', 'Doctor'),
(48, 'Olivia', 'Hall', '07395659006', 'olivia.hall@example.com', '510 Random St', 'Driver'),
(49, 'Amelia', 'Johnson', '07268404303', 'amelia.johnson@example.com', '481 Random St', 'Doctor'),
(50, 'Mia', 'Taylor', '07532875982', 'mia.taylor@example.com', '504 Random St', 'Nurse'),
(51, 'Sophia', 'Hall', '07500533648', 'sophia.hall@example.com', '299 Random St', 'Driver'),
(52, 'Ava', 'Walker', '07557460108', 'ava.walker@example.com', '548 Random St', 'Doctor'),
(53, 'Lucas', 'Young', '07304578275', 'lucas.young@example.com', '436 Random St', 'Engineer'),
(54, 'Lucas', 'Clark', '07999250266', 'lucas.clark@example.com', '710 Random St', 'Chef'),
(55, 'Ava', 'Allen', '07655632759', 'ava.allen@example.com', '181 Random St', 'Nurse'),
(56, 'Emma', 'Smith', '07835797816', 'emma.smith@example.com', '570 Random St', 'Driver'),
(57, 'Lucas', 'Wilson', '07782863100', 'lucas.wilson@example.com', '947 Random St', 'Engineer'),
(58, 'Ava', 'Young', '07363496117', 'ava.young@example.com', '382 Random St', 'Chef'),
(59, 'Emma', 'Brown', '07526167344', 'emma.brown@example.com', '852 Random St', 'Nurse'),
(60, 'Sophia', 'Johnson', '07934566732', 'sophia.johnson@example.com', '157 Random St', 'Nurse'),
(61, 'Olivia', 'Young', '07659649814', 'olivia.young@example.com', '464 Random St', 'Chef'),
(62, 'Lucas', 'Clark', '07757992042', 'lucas.clark@example.com', '787 Random St', 'Doctor'),
(63, 'Amelia', 'Johnson', '07814473920', 'amelia.johnson@example.com', '643 Random St', 'Teacher'),
(64, 'Liam', 'Taylor', '07648636578', 'liam.taylor@example.com', '217 Random St', 'Nurse'),
(65, 'Olivia', 'Walker', '07645943591', 'olivia.walker@example.com', '552 Random St', 'Doctor'),
(66, 'Olivia', 'Walker', '07131921843', 'olivia.walker@example.com', '485 Random St', 'Driver'),
(67, 'Lucas', 'Wilson', '07893007377', 'lucas.wilson@example.com', '136 Random St', 'Driver'),
(68, 'James', 'Johnson', '07562056118', 'james.johnson@example.com', '728 Random St', 'Teacher'),
(69, 'Liam', 'Clark', '07516497334', 'liam.clark@example.com', '402 Random St', 'Engineer'),
(70, 'Amelia', 'Clark', '07270245698', 'amelia.clark@example.com', '766 Random St', 'Teacher'),
(71, 'Emma', 'Brown', '07564687057', 'emma.brown@example.com', '610 Random St', 'Chef'),
(72, 'Mia', 'Hall', '07573239205', 'mia.hall@example.com', '877 Random St', 'Driver'),
(73, 'Mia', 'Walker', '07333704641', 'mia.walker@example.com', '715 Random St', 'Doctor'),
(74, 'James', 'Allen', '07953586875', 'james.allen@example.com', '394 Random St', 'Teacher'),
(75, 'Mason', 'Johnson', '07717255874', 'mason.johnson@example.com', '757 Random St', 'Doctor'),
(76, 'Noah', 'Young', '07266533211', 'noah.young@example.com', '661 Random St', 'Engineer'),
(77, 'Emma', 'Allen', '07318910130', 'emma.allen@example.com', '251 Random St', 'Doctor'),
(78, 'Sophia', 'Hall', '07791441737', 'sophia.hall@example.com', '648 Random St', 'Driver'),
(79, 'Noah', 'Young', '07741081303', 'noah.young@example.com', '605 Random St', 'Nurse'),
(80, 'Liam', 'Walker', '07411139732', 'liam.walker@example.com', '854 Random St', 'Driver'),
(81, 'Noah', 'Taylor', '07787667635', 'noah.taylor@example.com', '580 Random St', 'Driver'),
(82, 'Liam', 'Walker', '07628478860', 'liam.walker@example.com', '612 Random St', 'Driver'),
(83, 'Amelia', 'Johnson', '07127940141', 'amelia.johnson@example.com', '548 Random St', 'Nurse'),
(84, 'Emma', 'Johnson', '07257364879', 'emma.johnson@example.com', '972 Random St', 'Chef'),
(85, 'Mia', 'Young', '07945813252', 'mia.young@example.com', '490 Random St', 'Chef'),
(86, 'James', 'Walker', '07699158862', 'james.walker@example.com', '210 Random St', 'Driver'),
(87, 'Lucas', 'Brown', '07567201404', 'lucas.brown@example.com', '222 Random St', 'Nurse'),
(88, 'James', 'Young', '07366996835', 'james.young@example.com', '272 Random St', 'Nurse'),
(89, 'Liam', 'Johnson', '07869589345', 'liam.johnson@example.com', '406 Random St', 'Chef');

-- --------------------------------------------------------

--
-- Table structure for table `library_book`
--

CREATE TABLE `library_book` (
  `book_id` int(11) NOT NULL,
  `book_name` varchar(100) DEFAULT NULL,
  `book_author` varchar(100) DEFAULT NULL,
  `book_category` varchar(50) DEFAULT NULL,
  `isbn` varchar(30) DEFAULT NULL,
  `total_copies` int(11) DEFAULT NULL,
  `available_copies` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `library_book`
--

INSERT INTO `library_book` (`book_id`, `book_name`, `book_author`, `book_category`, `isbn`, `total_copies`, `available_copies`) VALUES
(1, 'Matilda', 'Roald Dahl', 'Fiction', '9780140328721', 5, 4),
(2, 'The BFG', 'Roald Dahl', 'Fiction', '9780140328714', 3, 3),
(3, 'Charlie and the Chocolate Factory', 'Roald Dahl', 'Fiction', '9780140328707', 4, 3),
(4, 'The Gruffalo', 'Julia Donaldson', 'Children', '9781509804761', 2, 2),
(5, 'Harry Potter and the Chamber of Secrets', 'J.K. Rowling', 'Fantasy', '9780579613902', 2, 0),
(6, 'The Very Hungry Caterpillar', 'Eric Carle', 'Children', '9780399226908', 3, 3),
(7, 'Charlottes Web', 'J.R.R. Tolkien', 'Fantasy', '9780391260504', 4, 4),
(8, 'The Hobbit', 'E.B. White', 'Adventure', '9780147057987', 6, 6),
(9, 'Green Eggs and Ham', 'Dr. Seuss', 'Children', '9780363687079', 6, 7),
(10, 'Where the Wild Things Are', 'J.R.R. Tolkien', 'Fiction', '9780819255520', 2, 2),
(11, 'The Cat in the Hat', 'J.R.R. Tolkien', 'Humor', '9780449818671', 5, 5),
(12, 'Goodnight Moon', 'Donot. Know', 'Adventure', '9780833002893', 5, 5),
(13, 'Winnie-the-Pooh', 'Maurice Sendak', 'Children', '9780224751862', 2, 2),
(14, 'The Gruffalo', 'Maurice Sendak', 'Children', '9780176242264', 2, 2),
(15, 'Diary of a Wimpy Kid', 'Maurice Sendak', 'Humor', '9780491931199', 4, 4),
(16, 'The Tale of Peter Rabbit', 'Beatrix Potter', 'Children', '9780723247702', 3, 3),
(17, 'The Secret Garden', 'Frances Hodgson Burnett', 'Fiction', '9780140366814', 4, 4),
(18, 'The Lion, the Witch and the Wardrobe', 'C.S. Lewis', 'Fantasy', '9780064404990', 5, 5),
(19, 'The Little Prince', 'Antoine de Saint-Exupéry', 'Fiction', '9780156012195', 3, 2),
(20, 'The Wind in the Willows', 'Kenneth Grahame', 'Fiction', '9780140366814', 4, 4),
(21, 'The Tale of Despereaux', 'Kate DiCamillo', 'Fiction', '9780763617226', 3, 3),
(22, 'The Phantom Tollbooth', 'Norton Juster', 'Fiction', '9780394820378', 2, 2),
(23, 'The Chronicles of Narnia: The Lion, the Witch and the Wardrobe', 'C.S. Lewis', 'Fantasy', '9780064404990', 4, 4);

-- --------------------------------------------------------

--
-- Table structure for table `medical_profile`
--

CREATE TABLE `medical_profile` (
  `profile_id` int(11) NOT NULL,
  `pupil_id` int(11) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_issues` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_profile`
--

INSERT INTO `medical_profile` (`profile_id`, `pupil_id`, `blood_type`, `allergies`, `chronic_issues`) VALUES
(1, 1, 'O+', 'Peanuts', 'None'),
(2, 2, 'A-', 'None', 'Asthma'),
(3, 3, 'AB+', 'Gluten', 'None'),
(4, 4, 'O-', 'Gluten', 'None'),
(5, 5, 'A+', 'Milk', 'Diabetes'),
(6, 6, 'AB-', 'Shellfish', 'None'),
(7, 7, 'O-', 'None', 'None'),
(8, 8, 'B+', 'None', 'None'),
(9, 9, 'A+', 'Pollen', 'None'),
(10, 10, 'O+', 'None', 'None'),
(11, 11, 'AB-', 'Peanuts', 'None'),
(12, 12, 'B-', 'Milk', 'None'),
(13, 13, 'O+', 'Shellfish', 'None'),
(14, 14, 'A-', 'Pollen', 'Asthma'),
(15, 15, 'AB+', 'Gluten', 'None'),
(16, 16, 'O-', 'Peanuts', 'None'),
(17, 17, 'A+', 'Milk', 'Diabetes'),
(18, 18, 'AB-', 'Shellfish', 'None'),
(19, 19, 'O-', 'Gluten', 'None'),
(20, 20, 'B+', 'Pollen', 'Asthma'),
(21, 21, 'A+', 'Peanuts', 'None'),
(22, 22, 'O+', 'Milk', 'Diabetes'),
(23, 23, 'AB-', 'Shellfish', 'None'),
(24, 24, 'B-', 'Gluten', 'None'),
(25, 25, 'O+', 'Pollen', 'Asthma'),
(26, 26, 'A+', 'Milk', 'None'),
(27, 27, 'AB+', 'None', 'None'),
(28, 28, 'O-', 'None', 'None'),
(29, 29, 'B+', 'None', 'None'),
(30, 30, 'A+', 'Pollen', 'None'),
(31, 31, 'O+', 'None', 'None'),
(32, 32, 'AB-', 'Peanuts', 'None'),
(33, 33, 'B-', 'Milk', 'None'),
(34, 34, 'O+', 'Shellfish', 'None'),
(35, 35, 'A-', 'Pollen', 'Asthma'),
(36, 36, 'O+', 'Peanuts', 'None'),
(37, 37, 'A-', 'None', 'Asthma'),
(38, 38, 'AB+', 'Gluten', 'None'),
(39, 39, 'O-', 'Gluten', 'None'),
(40, 40, 'A+', 'Milk', 'Diabetes'),
(41, 41, 'AB-', 'Shellfish', 'None'),
(42, 42, 'O-', 'None', 'None'),
(43, 43, 'B+', 'None', 'None'),
(44, 44, 'A+', 'Pollen', 'None'),
(45, 45, 'O+', 'None', 'None'),
(46, 46, 'O+', 'Peanuts', 'None'),
(47, 47, 'A-', 'None', 'Asthma'),
(48, 48, 'AB+', 'Gluten', 'None'),
(49, 49, 'O-', 'Gluten', 'None'),
(50, 50, 'A+', 'Milk', 'Diabetes'),
(51, 51, 'AB-', 'Shellfish', 'None'),
(52, 52, 'O-', 'None', 'None'),
(53, 53, 'B+', 'None', 'None'),
(54, 54, 'A+', 'Pollen', 'None'),
(55, 55, 'O+', 'None', 'None'),
(56, 56, 'AB-', 'Peanuts', 'None'),
(57, 57, 'B-', 'Milk', 'None'),
(58, 58, 'O+', 'Shellfish', 'None'),
(59, 59, 'A-', 'Pollen', 'Asthma'),
(60, 60, 'AB+', 'Gluten', 'None'),
(61, 61, 'O-', 'Peanuts', 'None'),
(62, 62, 'A+', 'Milk', 'Diabetes'),
(63, 63, 'AB-', 'Shellfish', 'None'),
(64, 64, 'O+', 'Peanuts', 'None'),
(65, 65, 'A-', 'None', 'Asthma'),
(66, 66, 'AB+', 'Gluten', 'None'),
(67, 67, 'O-', 'Gluten', 'None'),
(68, 68, 'A+', 'Milk', 'Diabetes'),
(69, 69, 'AB-', 'Shellfish', 'None'),
(70, 70, 'O-', 'None', 'None'),
(71, 71, 'B+', 'None', 'None'),
(72, 72, 'O+', 'None', 'None'),
(73, 73, 'A-', 'None', 'None'),
(74, 74, 'AB+', 'None', 'None'),
(75, 75, 'O-', 'None', 'None'),
(76, 76, 'A+', 'None', 'None'),
(77, 77, 'AB-', 'None', 'None'),
(78, 78, 'O-', 'None', 'None'),
(79, 79, 'B+', 'None', 'None'),
(80, 80, 'A+', 'None', 'None'),
(81, 81, 'O+', 'None', 'None'),
(82, 82, 'AB-', 'None', 'None'),
(83, 83, 'B-', 'None', 'None'),
(84, 84, 'O+', 'None', 'None'),
(85, 85, 'AB+', 'Gluten', 'None'),
(86, 86, 'O+', 'Pollen', 'Asthma'),
(87, 87, 'A+', 'Shellfish', 'None'),
(88, 88, 'O-', 'Milk', 'None'),
(89, 89, 'O-', 'Milk', 'None');

-- --------------------------------------------------------

--
-- Table structure for table `medical_record`
--

CREATE TABLE `medical_record` (
  `record_id` int(11) NOT NULL,
  `pupil_id` int(11) DEFAULT NULL,
  `medical_condition` varchar(255) DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_recorded` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_record`
--

INSERT INTO `medical_record` (`record_id`, `pupil_id`, `medical_condition`, `treatment`, `notes`, `date_recorded`) VALUES
(1, 1, 'Asthma Attack', 'Inhaler prescribed', 'Mild symptoms during PE', '2024-11-15'),
(2, 3, 'Allergic Reaction', 'Antihistamines administered', 'Swelling around the eyes', '2024-12-01'),
(3, 54, 'Flu', 'Rest and hydration advised', 'Fever and cough', '2025-01-10'),
(4, 5, 'Headache', 'Pain relievers prescribed', 'Mild headache during class', '2025-02-05'),
(5, 14, 'Stomachache', 'Dietary changes recommended', 'Complaints of stomach pain', '2025-03-20'),
(6, 39, 'Cold', 'Rest and hydration advised', 'Mild cold symptoms', '2025-03-25'),
(7, 8, 'Fever', 'Rest and hydration advised', 'High fever during class', '2025-04-01'),
(8, 33, 'Allergic Reaction', 'Antihistamines administered', 'Rash on arms and legs', '2025-04-05'),
(9, 10, 'Flu', 'Rest and hydration advised', 'Fever and cough', '2025-04-10'),
(10, 11, 'Headache', 'Pain relievers prescribed', 'Mild headache during class', '2025-04-15'),
(11, 26, 'Stomachache', 'Dietary changes recommended', 'Complaints of stomach pain', '2025-04-20'),
(12, 77, 'Cold', 'Rest and hydration advised', 'Mild cold symptoms', '2025-04-25'),
(13, 14, 'Fever', 'Rest and hydration advised', 'High fever during class', '2025-04-30'),
(14, 89, 'Allergic Reaction', 'Antihistamines administered', 'Rash on arms and legs', '2025-05-05'),
(15, 12, 'Flu', 'Rest and hydration advised', 'Fever and cough', '2025-05-10'),
(16, 13, 'Headache', 'Pain relievers prescribed', 'Mild headache during class', '2025-05-15'),
(17, 15, 'Stomachache', 'Dietary changes recommended', 'Complaints of stomach pain', '2025-05-20'),
(18, 16, 'Cold', 'Rest and hydration advised', 'Mild cold symptoms', '2025-05-25'),
(20, 18, 'Allergic Reaction', 'Antihistamines administered', 'Rash on arms and legs', '2025-06-05'),
(21, 19, 'Flu', 'Rest and hydration advised', 'Fever and cough', '2025-06-10'),
(22, 20, 'Headache', 'Pain relievers prescribed', 'Mild headache during class', '2025-06-15'),
(23, 21, 'Stomachache', 'Dietary changes recommended', 'Complaints of stomach pain', '2025-06-20');

-- --------------------------------------------------------

--
-- Table structure for table `pupil`
--

CREATE TABLE `pupil` (
  `pupil_id` int(11) NOT NULL,
  `p_first_name` varchar(30) DEFAULT NULL,
  `p_last_name` varchar(30) DEFAULT NULL,
  `p_gender` enum('Male','Female') DEFAULT NULL,
  `p_birth_date` date DEFAULT NULL,
  `p_address` varchar(255) DEFAULT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pupil`
--

INSERT INTO `pupil` (`pupil_id`, `p_first_name`, `p_last_name`, `p_gender`, `p_birth_date`, `p_address`, `class_id`) VALUES
(1, 'Alice', 'Smith', 'Female', '2019-10-25', '471 Random St', 1),
(2, 'Bob', 'Brown', 'Male', '2019-11-08', '286 Random St', 1),
(3, 'Mia', 'Clark', 'Male', '2019-10-25', '471 Random St', 1),
(4, 'Amelia', 'Brown', 'Male', '2019-11-08', '286 Random St', 1),
(5, 'Mason', 'Young', 'Female', '2019-07-16', '135 Random St', 1),
(6, 'Emma', 'Wilson', 'Female', '2019-11-08', '576 Random St', 1),
(7, 'Ava', 'Brown', 'Female', '2019-12-08', '693 Random St', 1),
(8, 'Noah', 'Taylor', 'Male', '2019-09-19', '867 Random St', 1),
(9, 'Lucas', 'Brown', 'Female', '2019-07-06', '600 Random St', 1),
(10, 'Mia', 'Wilson', 'Female', '2019-02-18', '933 Random St', 1),
(11, 'Mason', 'Allen', 'Female', '2019-07-31', '782 Random St', 1),
(12, 'Isla', 'Clark', 'Female', '2019-08-08', '940 Random St', 1),
(13, 'Amelia', 'Johnson', 'Male', '2019-05-13', '354 Random St', 1),
(14, 'Mason', 'Smith', 'Male', '2018-11-13', '291 Random St', 2),
(15, 'Sophia', 'Smith', 'Female', '2018-12-23', '404 Random St', 2),
(16, 'Amelia', 'Smith', 'Female', '2018-08-08', '105 Random St', 2),
(17, 'Mason', 'Johnson', 'Female', '2018-08-24', '867 Random St', 2),
(18, 'James', 'Hall', 'Female', '2018-05-08', '223 Random St', 2),
(19, 'Mason', 'Brown', 'Male', '2018-11-04', '837 Random St', 2),
(20, 'Liam', 'Johnson', 'Male', '2018-08-08', '806 Random St', 2),
(21, 'Amelia', 'Wilson', 'Male', '2018-03-11', '300 Random St', 2),
(22, 'Amelia', 'Taylor', 'Male', '2018-03-23', '998 Random St', 2),
(23, 'Olivia', 'Hall', 'Female', '2018-04-12', '196 Random St', 2),
(24, 'Isla', 'Smith', 'Female', '2018-12-05', '704 Random St', 2),
(25, 'Lucas', 'Wilson', 'Female', '2017-04-15', '965 Random St', 3),
(26, 'Liam', 'Clark', 'Female', '2017-12-13', '504 Random St', 3),
(27, 'Isla', 'Johnson', 'Female', '2017-09-19', '818 Random St', 3),
(28, 'Ava', 'Young', 'Male', '2017-01-23', '720 Random St', 3),
(29, 'Mia', 'Brown', 'Female', '2017-05-26', '567 Random St', 3),
(30, 'Mason', 'Allen', 'Female', '2017-01-11', '570 Random St', 3),
(31, 'Liam', 'Wilson', 'Male', '2017-06-26', '633 Random St', 3),
(32, 'Sophia', 'Wilson', 'Female', '2017-04-01', '327 Random St', 3),
(33, 'Liam', 'Allen', 'Female', '2017-07-23', '959 Random St', 3),
(34, 'Lucas', 'Taylor', 'Female', '2017-01-11', '679 Random St', 3),
(35, 'James', 'Wilson', 'Female', '2017-03-12', '975 Random St', 3),
(36, 'Olivia', 'Taylor', 'Male', '2017-04-09', '361 Random St', 3),
(37, 'Olivia', 'Allen', 'Male', '2017-05-17', '238 Random St', 3),
(38, 'Lucas', 'Walker', 'Female', '2017-11-23', '593 Random St', 3),
(39, 'Amelia', 'Clark', 'Female', '2016-06-12', '449 Random St', 4),
(40, 'Liam', 'Taylor', 'Male', '2016-10-23', '396 Random St', 4),
(41, 'Ava', 'Wilson', 'Male', '2016-03-31', '714 Random St', 4),
(42, 'James', 'Johnson', 'Male', '2016-08-13', '836 Random St', 4),
(43, 'Isla', 'Young', 'Male', '2016-07-29', '834 Random St', 4),
(44, 'Olivia', 'Hall', 'Female', '2016-01-10', '223 Random St', 4),
(45, 'Noah', 'Wilson', 'Male', '2016-01-18', '409 Random St', 4),
(46, 'James', 'Walker', 'Female', '2016-02-15', '446 Random St', 4),
(47, 'Liam', 'Brown', 'Male', '2016-01-26', '120 Random St', 4),
(48, 'Liam', 'Hall', 'Male', '2016-05-24', '510 Random St', 4),
(49, 'James', 'Johnson', 'Male', '2016-03-01', '481 Random St', 4),
(50, 'Noah', 'Taylor', 'Female', '2016-12-31', '504 Random St', 4),
(51, 'Emma', 'Hall', 'Male', '2016-02-02', '299 Random St', 4),
(52, 'Ava', 'Walker', 'Female', '2015-03-02', '548 Random St', 5),
(53, 'Liam', 'Young', 'Male', '2015-05-11', '436 Random St', 5),
(54, 'Olivia', 'Clark', 'Male', '2015-07-27', '710 Random St', 5),
(55, 'Mason', 'Allen', 'Male', '2015-09-27', '181 Random St', 5),
(56, 'Olivia', 'Smith', 'Female', '2015-02-18', '570 Random St', 5),
(57, 'Isla', 'Wilson', 'Female', '2015-06-11', '947 Random St', 5),
(58, 'Ava', 'Young', 'Male', '2015-01-27', '382 Random St', 5),
(59, 'James', 'Brown', 'Male', '2015-12-22', '852 Random St', 5),
(60, 'Sophia', 'Johnson', 'Female', '2015-12-04', '157 Random St', 5),
(61, 'Lucas', 'Young', 'Male', '2015-03-09', '464 Random St', 5),
(62, 'Sophia', 'Clark', 'Male', '2015-05-08', '787 Random St', 5),
(63, 'Olivia', 'Johnson', 'Male', '2015-04-24', '643 Random St', 5),
(64, 'Sophia', 'Taylor', 'Female', '2015-11-14', '217 Random St', 5),
(65, 'Olivia', 'Walker', 'Male', '2015-06-24', '552 Random St', 5),
(66, 'Olivia', 'Walker', 'Female', '2014-08-31', '485 Random St', 6),
(67, 'James', 'Wilson', 'Male', '2014-08-25', '136 Random St', 6),
(68, 'Emma', 'Johnson', 'Male', '2014-10-31', '728 Random St', 6),
(69, 'James', 'Clark', 'Female', '2014-10-17', '402 Random St', 6),
(70, 'James', 'Clark', 'Female', '2014-09-16', '766 Random St', 6),
(71, 'Liam', 'Brown', 'Female', '2014-01-03', '610 Random St', 6),
(72, 'Olivia', 'Hall', 'Female', '2014-05-26', '877 Random St', 6),
(73, 'Olivia', 'Walker', 'Male', '2014-03-10', '715 Random St', 6),
(74, 'Olivia', 'Allen', 'Male', '2014-04-20', '394 Random St', 6),
(75, 'Olivia', 'Johnson', 'Male', '2014-04-18', '757 Random St', 6),
(76, 'Liam', 'Young', 'Female', '2014-08-27', '661 Random St', 6),
(77, 'Emma', 'Allen', 'Male', '2014-02-16', '251 Random St', 6),
(78, 'Liam', 'Hall', 'Female', '2014-11-27', '648 Random St', 6),
(79, 'Isla', 'Young', 'Female', '2013-11-20', '605 Random St', 7),
(80, 'Olivia', 'Walker', 'Female', '2013-01-14', '854 Random St', 7),
(81, 'Isla', 'Taylor', 'Male', '2013-04-22', '580 Random St', 7),
(82, 'Liam', 'Walker', 'Female', '2013-07-07', '612 Random St', 7),
(83, 'Noah', 'Johnson', 'Female', '2013-05-21', '548 Random St', 7),
(84, 'James', 'Johnson', 'Female', '2013-09-26', '972 Random St', 7),
(85, 'Mia', 'Young', 'Female', '2013-01-13', '490 Random St', 7),
(86, 'Mason', 'Walker', 'Female', '2013-06-17', '210 Random St', 7),
(87, 'Emma', 'Brown', 'Female', '2013-04-04', '222 Random St', 7),
(88, 'Liam', 'Young', 'Male', '2013-06-01', '272 Random St', 7),
(89, 'Mason', 'Johnson', 'Female', '2013-12-07', '406 Random St', 7),
(90, 'A', 'B', 'Male', '2015-05-08', 'AAA Str', 6),
(91, 'AAA', 'ZZZ', 'Female', '2016-11-04', 'ABC Str', 5);

-- --------------------------------------------------------

--
-- Table structure for table `pupil_guardian`
--

CREATE TABLE `pupil_guardian` (
  `pupil_id` int(11) NOT NULL,
  `guardian_id` int(11) NOT NULL,
  `relationship_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pupil_guardian`
--

INSERT INTO `pupil_guardian` (`pupil_id`, `guardian_id`, `relationship_type`) VALUES
(1, 1, 'Father'),
(2, 2, 'Mother'),
(3, 3, 'Father'),
(4, 4, 'Father'),
(5, 5, 'Father'),
(6, 6, 'Father'),
(7, 7, 'Guardian'),
(8, 8, 'Father'),
(9, 9, 'Mother'),
(10, 10, 'Mother'),
(11, 11, 'Father'),
(12, 12, 'Mother'),
(13, 13, 'Mother'),
(14, 14, 'Mother'),
(15, 15, 'Mother'),
(16, 16, 'Mother'),
(17, 17, 'Mother'),
(18, 18, 'Father'),
(19, 19, 'Mother'),
(20, 20, 'Father'),
(21, 21, 'Mother'),
(22, 22, 'Guardian'),
(24, 24, 'Guardian'),
(25, 25, 'Mother'),
(26, 26, 'Mother'),
(27, 27, 'Guardian'),
(28, 28, 'Father'),
(29, 29, 'Guardian'),
(30, 30, 'Guardian'),
(31, 31, 'Mother'),
(32, 32, 'Mother'),
(33, 33, 'Guardian'),
(34, 34, 'Guardian'),
(35, 35, 'Father'),
(36, 36, 'Father'),
(37, 37, 'Father'),
(38, 38, 'Father'),
(39, 39, 'Mother'),
(40, 40, 'Father'),
(41, 41, 'Guardian'),
(42, 42, 'Mother'),
(43, 43, 'Guardian'),
(44, 44, 'Father'),
(45, 45, 'Guardian'),
(46, 46, 'Father'),
(47, 47, 'Mother'),
(48, 48, 'Father'),
(49, 49, 'Guardian'),
(50, 50, 'Mother'),
(51, 51, 'Guardian'),
(52, 52, 'Mother'),
(53, 53, 'Mother'),
(54, 54, 'Father'),
(55, 55, 'Father'),
(56, 56, 'Mother'),
(57, 57, 'Father'),
(58, 58, 'Father'),
(59, 59, 'Guardian'),
(60, 60, 'Mother'),
(61, 61, 'Father'),
(62, 62, 'Father'),
(63, 63, 'Father'),
(64, 64, 'Father'),
(65, 65, 'Guardian'),
(66, 66, 'Father'),
(67, 67, 'Mother'),
(68, 68, 'Guardian'),
(69, 69, 'Father'),
(70, 70, 'Mother'),
(71, 71, 'Father'),
(72, 72, 'Guardian'),
(73, 73, 'Guardian'),
(74, 74, 'Mother'),
(75, 75, 'Mother'),
(76, 76, 'Father'),
(77, 77, 'Guardian'),
(78, 78, 'Mother'),
(79, 79, 'Father'),
(80, 80, 'Mother'),
(81, 81, 'Guardian'),
(82, 82, 'Father'),
(83, 83, 'Father'),
(84, 84, 'Mother'),
(85, 85, 'Guardian'),
(86, 86, 'Mother'),
(87, 87, 'Father'),
(88, 88, 'Guardian'),
(89, 89, 'Mother');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `teacher_id` int(11) NOT NULL,
  `t_first_name` varchar(30) DEFAULT NULL,
  `t_last_name` varchar(30) DEFAULT NULL,
  `t_gender` enum('Male','Female') DEFAULT NULL,
  `t_birth_date` date DEFAULT NULL,
  `t_phone` varchar(20) DEFAULT NULL,
  `t_email` varchar(100) DEFAULT NULL,
  `t_address` varchar(255) DEFAULT NULL,
  `t_annual_salary` decimal(10,2) DEFAULT NULL,
  `t_hire_date` date NOT NULL,
  `t_qualification` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`teacher_id`, `t_first_name`, `t_last_name`, `t_gender`, `t_birth_date`, `t_phone`, `t_email`, `t_address`, `t_annual_salary`, `t_hire_date`, `t_qualification`) VALUES
(1, 'Alice', 'Johnson', 'Female', '1985-03-22', '1234567890', 'alice.johnson@example.com', '000 Maple St', 45000.00, '2010-01-13', 'Bachelor'),
(2, 'Michael', 'Lee', 'Male', '1980-01-15', '1111234567', 'michael.lee@example.com', '101 Elm St', 42000.00, '2015-07-23', 'Bachelor'),
(3, 'Sophia', 'Wong', 'Female', '1990-04-22', '2222345678', 'sophia.wong@example.com', '202 Cedar St', 41000.00, '2012-03-01', 'Bachelor'),
(4, 'David', 'Garcia', 'Male', '1982-08-30', '3333456789', 'david.garcia@example.com', '303 Birch St', 43000.00, '2015-09-04', 'Master'),
(5, 'Olivia', 'Martins', 'Female', '1988-12-01', '4444567890', 'olivia.martins@example.com', '404 Spruce St', 40500.00, '2013-02-13', 'Bachelor'),
(6, 'Ethan', 'Patel', 'Male', '1979-11-10', '5555678901', 'ethan.patel@example.com', '505 Willow St', 44000.00, '2012-07-05', 'Master'),
(7, 'Grace', 'Kim', 'Female', '1992-06-18', '6666789012', 'grace.kim@example.com', '606 Cherry St', 44000.00, '2017-08-24', 'Master'),
(8, 'Brian', 'Smith', 'Male', '1979-11-15', '1234567891', 'brian.smith@example.com', '101 Oak Ave', 42000.00, '2010-09-01', 'Master'),
(9, 'Carol', 'Lee', 'Female', '1982-07-08', '1234567892', 'carol.lee@example.com', '202 Pine Rd', 41000.00, '2011-08-15', 'PhD'),
(10, 'David', 'Nguyen', 'Male', '1990-05-12', '1234567893', 'david.nguyen@example.com', '303 Birch Ln', 43000.00, '2015-01-10', 'Bachelor'),
(11, 'Eva', 'Martinez', 'Female', '1987-09-20', '1234567894', 'eva.martinez@example.com', '404 Cedar Dr', 44000.00, '2013-04-22', 'Master'),
(12, 'Frank', 'OConnor', 'Male', '1983-02-18', '1234567895', 'frank.oconnor@example.com', '505 Walnut St', 39500.00, '2009-11-03', 'Bachelor'),
(13, 'Grace', 'Kim', 'Female', '1991-12-01', '1234567896', 'grace.kim@example.com', '606 Chestnut Ave', 40500.00, '2016-07-19', 'Master'),
(14, 'Henry', 'Zhao', 'Male', '1986-08-10', '1234567897', 'henry.zhao@example.com', '707 Spruce Rd', 45000.00, '2012-03-05', 'PhD'),
(15, 'Isla', 'Williams', 'Female', '1989-04-25', '1234567898', 'isla.williams@example.com', '808 Fir Ln', 41500.00, '2014-10-27', 'Bachelor'),
(16, 'Jack', 'Brown', 'Male', '1984-06-30', '1234567899', 'jack.brown@example.com', '909 Willow Dr', 46000.00, '2008-05-14', 'Master'),
(17, 'Kara', 'Singh', 'Female', '1988-10-05', '1234567800', 'kara.singh@example.com', '111 Maple St', 43500.00, '2013-09-09', 'Master'),
(18, 'Leo', 'Davis', 'Male', '1977-01-13', '1234567801', 'leo.davis@example.com', '222 Oak Ave', 47000.00, '2005-02-17', 'PhD'),
(19, 'Mia', 'Chen', 'Female', '1992-03-07', '1234567802', 'mia.chen@example.com', '333 Pine Rd', 40000.00, '2017-08-23', 'Bachelor'),
(20, 'Noah', 'Ali', 'Male', '1981-09-17', '1234567803', 'noah.ali@example.com', '444 Birch Ln', 42000.00, '2010-01-30', 'Master'),
(21, 'Olivia', 'Garcia', 'Female', '1986-11-29', '1234567804', 'olivia.garcia@example.com', '555 Cedar Dr', 41000.00, '2011-06-11', 'Bachelor'),
(22, 'Peter', 'Evans', 'Male', '1990-07-21', '1234567805', 'peter.evans@example.com', '888 Walnut St', 48000.00, '2015-03-18', 'PhD');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','pupil') NOT NULL,
  `linked_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `linked_id`) VALUES
(4, 'admin', '12345', 'admin', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `book_loan`
--
ALTER TABLE `book_loan`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `pupil_id` (`pupil_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_history`
--
ALTER TABLE `class_history`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `guardian`
--
ALTER TABLE `guardian`
  ADD PRIMARY KEY (`guardian_id`);

--
-- Indexes for table `library_book`
--
ALTER TABLE `library_book`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `medical_profile`
--
ALTER TABLE `medical_profile`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `pupil_id` (`pupil_id`);

--
-- Indexes for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `pupil_id` (`pupil_id`);

--
-- Indexes for table `pupil`
--
ALTER TABLE `pupil`
  ADD PRIMARY KEY (`pupil_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `pupil_guardian`
--
ALTER TABLE `pupil_guardian`
  ADD PRIMARY KEY (`pupil_id`,`guardian_id`),
  ADD KEY `guardian_id` (`guardian_id`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `book_loan`
--
ALTER TABLE `book_loan`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `class_history`
--
ALTER TABLE `class_history`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `guardian`
--
ALTER TABLE `guardian`
  MODIFY `guardian_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `library_book`
--
ALTER TABLE `library_book`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `medical_profile`
--
ALTER TABLE `medical_profile`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `medical_record`
--
ALTER TABLE `medical_record`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `pupil`
--
ALTER TABLE `pupil`
  MODIFY `pupil_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `book_loan`
--
ALTER TABLE `book_loan`
  ADD CONSTRAINT `book_loan_ibfk_1` FOREIGN KEY (`pupil_id`) REFERENCES `pupil` (`pupil_id`),
  ADD CONSTRAINT `book_loan_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `library_book` (`book_id`);

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `class_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`);

--
-- Constraints for table `class_history`
--
ALTER TABLE `class_history`
  ADD CONSTRAINT `class_history_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`);

--
-- Constraints for table `medical_profile`
--
ALTER TABLE `medical_profile`
  ADD CONSTRAINT `medical_profile_ibfk_1` FOREIGN KEY (`pupil_id`) REFERENCES `pupil` (`pupil_id`);

--
-- Constraints for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD CONSTRAINT `medical_record_ibfk_1` FOREIGN KEY (`pupil_id`) REFERENCES `pupil` (`pupil_id`);

--
-- Constraints for table `pupil`
--
ALTER TABLE `pupil`
  ADD CONSTRAINT `pupil_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`);

--
-- Constraints for table `pupil_guardian`
--
ALTER TABLE `pupil_guardian`
  ADD CONSTRAINT `pupil_guardian_ibfk_1` FOREIGN KEY (`pupil_id`) REFERENCES `pupil` (`pupil_id`),
  ADD CONSTRAINT `pupil_guardian_ibfk_2` FOREIGN KEY (`guardian_id`) REFERENCES `guardian` (`guardian_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
