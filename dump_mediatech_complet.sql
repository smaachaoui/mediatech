-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: mediatech
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `book`
--

DROP TABLE IF EXISTS `book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_image` longtext COLLATE utf8mb4_unicode_ci,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `translator` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_date` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page_count` int DEFAULT NULL,
  `isbn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `synopsis` longtext COLLATE utf8mb4_unicode_ci,
  `google_books_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_CBE5A33179FDCE08` (`google_books_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book`
--

LOCK TABLES `book` WRITE;
/*!40000 ALTER TABLE `book` DISABLE KEYS */;
INSERT INTO `book` VALUES (7,'Dune','https://covers.openlibrary.org/b/isbn/9782266320481-L.jpg','Frank Herbert',NULL,NULL,NULL,'Science-fiction',NULL,'978-2266320481','Une grande fresque de science-fiction politique et mystique.','dune-fixture-1','2026-02-04 23:16:26'),(8,'Le Seigneur des Anneaux','https://covers.openlibrary.org/b/isbn/9782266286268-L.jpg','J.R.R. Tolkien',NULL,NULL,NULL,'Fantasy',NULL,'978-2266286268','Un voyage épique pour détruire l\'Anneau Unique.','lotr-fixture-1','2026-02-04 23:16:26'),(9,'Sapiens','https://covers.openlibrary.org/b/isbn/9782226257017-L.jpg','Yuval Noah Harari',NULL,NULL,NULL,'Histoire',NULL,'978-2226257017','Une histoire globale de l\'humanité.','sapiens-fixture-1','2026-02-04 23:16:26'),(10,'Alceste','https://books.google.com/books/content?id=0vfYoQEACAAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api','Euripides','Hachette Livre - BNF',NULL,'2013-07-14',NULL,164,'9782013282178','Alceste / Euripide; expliquee litteralement, traduite en francais et annotee par F. de Parnajon, ...]<br>Date de l\'edition originale: 1888<br><br>Ce livre est la reproduction fidele d\'une oeuvre publiee avant 1920 et fait partie d\'une collection de livres reimprimes a la demande editee par Hachette Livre, dans le cadre d\'un partenariat avec la Bibliotheque nationale de France, offrant l\'opportunite d\'acceder a des ouvrages anciens et souvent rares issus des fonds patrimoniaux de la BnF.<br>Les oeuvres faisant partie de cette collection ont ete numerisees par la BnF et sont presentes sur Gallica, sa bibliotheque numerique.<br><br>En entreprenant de redonner vie a ces ouvrages au travers d\'une collection de livres reimprimes a la demande, nous leur donnons la possibilite de rencontrer un public elargi et participons a la transmission de connaissances et de savoirs parfois difficilement accessibles.<br>Nous avons cherche a concilier la reproduction fidele d\'un livre ancien a partir de sa version numerisee avec le souci d\'un confort de lecture optimal. Nous esperons que les ouvrages de cette nouvelle collection vous apporteront entiere satisfaction.<br><br>Pour plus d\'informations, rendez-vous sur www.hachettebnf.fr<br>','0vfYoQEACAAJ','2026-02-05 10:04:24'),(11,'Star Wars - Dark Vador','https://books.google.com/books/content?id=xWeqCgAAQBAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api','Haden Blackman, Augustin Alessio','Delcourt',NULL,'2015-10-07',NULL,128,'9782756077871','Nouvelle série consacrée à l’un des personnages les plus emblématiques de la saga des étoiles, la terreur de l’empire galactique : Dark Vador.<br><br> Les soubresauts de l’avènement de l’Empire se sont fait sentir jusqu’aux confins de la galaxie. Dark Vador doit calmer les disputes locales et récupérer les croiseurs interstellaires perdus. Sur Coruscant, il assiste à la remise de leurs diplômes de jeunes recrues venues des meilleures académies militaires de la galaxie.','xWeqCgAAQBAJ','2026-02-05 12:11:16'),(12,'Eragon poche, Tome 01','https://books.google.com/books/content?id=jWaVDAAAQBAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api','Christopher Paolini, Bertrand Ferrier','Bayard Jeunesse',NULL,'2023-11-08',NULL,698,'9782747059435','Eragon n\'a que quinze ans, mais le destin de l\'Empire est désormais entre ses mains ! C\'est en poursuivant une biche dans la montagne que le jeune Eragon, quinze ans, tombe sur une magnifique pierre bleue, qui s\'avère être... un oeuf de dragon ! Fasciné, il l\'emporte à Carvahall, le village où il vit pauvrement avec son oncle et son cousin. Il n\'imagine pas alors qu\'une dragonne, porteuse d\'un héritage ancestral, va en éclore... Très vite, la vie d\'Eragon est bouleversée. Contraint de quitter les siens, le jeune homme s\'engage dans une quête qui le mènera aux confins de l\'empire de l\'Alagaësia. Armé de son épée et guidé par les conseils de Brom, le vieux conteur, Eragon va devoir affronter avec sa dragonne les terribles ennemis envoyés par le roi Galbatorix, dont la malveillance démoniaque ne connaît aucune limite.','jWaVDAAAQBAJ','2026-02-05 22:00:32'),(13,'Eragon poche, Tome 02','https://books.google.com/books/content?id=12aVDAAAQBAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api','Christopher Paolini, Marie-Hélène DELVAL','Bayard Jeunesse',NULL,'2023-11-08',NULL,808,'9782747059466','Une plongée dans les ténèbres : les certitudes s\'évanouissent et les forces du mal règnent. Eragon et sa dragonne, Saphira, sortent à peine de la victoire de Farthen Dûr contre les Urgals, qu\'une nouvelle horde de monstres surgit... Ajihad, le chef des Vardens, est tué. Nommée par le Conseil des Anciens, Nasuada, la fille du vieux chef, prend la tête des rebelles. Eragon et Saphira lui prêtent allégeance avant d\'entreprendre un long et périlleux voyage vers Ellesméra, le royaume des elfes, où ils doivent suivre leur formation. Là, ils découvrent avec stupeur qu\'Arya est la fille de la reine Islanzadì. Cette dernière leur présente en secret un dragon d\'or, Glaedr, chevauché par un Dragonnier, Oromis, qui n\'est autre que le Sage-en-Deuil, l\'Estropié-qui-est-Tout, le personnage qui était apparu à Eragon lorsqu\'il délirait, blessé par l\'Ombre. Oromis va devenir leur maître. Le jeune Dragonnier commence sa formation. Mais il n\'est pas au bout de ses découvertes. Des révélations dérangeantes entament sa confiance. Pendant longtemps, Eragon ne saura qui croire. Or, le danger n\'est toujours pas écarté : à Carvahall, Roran, son cousin, a engagé le combat contre les Ra\'zacs. Ceux-ci, persuadés qu\'il détient l\'oeuf qu\'Eragon avait trouvé sur la Crête, finissent par enlever sa fiancée. Prêt à tout pour la sauver, Roran comprend cependant qu\'il n\'est pas de taille à les affronter. Il convainc les villageois de traverser la Crête pour rejoindre les rebelles au Surda, en guerre contre le roi de l\'Empire, le cruel Galbatorix.','12aVDAAAQBAJ','2026-02-05 22:00:38'),(14,'Eragon poche, Tome 03','https://books.google.com/books/content?id=SWiVDAAAQBAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api','Christopher Paolini, Danièle Laruelle','Bayard Jeunesse',NULL,'2023-11-08',NULL,840,'9782747059497','Eragon a une double promesse à tenir : aider Roran à délivrer sa fiancée, Katrina , prisonnière des Ra\'zacs, et venger la mort de son oncle Garrow. Saphira emmène les deux cousins jusqu\'à Helgrind, repaire des monstres. Or, depuis que Murtagh lui a repris Zar\'oc, l\'épée que Brom lui avait donnée, Eragon n\'est plus armé que du bâton du vieux conteur. Cependant, depuis la Cérémonie du Sang, le jeune Dragonnier ne cesse de se transformer, acquérant peu à peu les fabuleuses capacités d\'un elfe. Et Roran mérite plus que jamais son surnom de Puissant Marteau. Quant à Saphira, elle est une combattante redoutable. Ainsi commence cette troisième partie de l\'Héritage, où l\'on verra l\'intrépide Nasuada, chef des Vardens, subir avec bravoure l\'épreuve des Longs Couteaux ; les Vardens affronter les soldats démoniaques de Galbatorix ; Arya et Eragon rivaliser de délicates inventions magiques ; Murtagh chevauchant Thorn, son dragon rouge, batailler contre Eragon et Saphira. On s\'enfoncera dans les galeries souterraines des nains ; on se laissera séduire par Nar Garzhvog, le formidable Urgal, et par l\'énigmatique Lupusänghren, l\'elfe au pelage de loup ; on retrouvera avec bonheur Oromis et Glaedr, le dragon d\'or ; on constatera avec jubilation que Saphira montre toujours un goût certain pour l\'hydromel. Et on saura enfin pourquoi le roman porte ce titre énigmatique : Brisingr, Feu en ancien langage.','SWiVDAAAQBAJ','2026-02-05 22:00:41'),(15,'Eragon poche, Tome 04','https://books.google.com/books/content?id=uWiVDAAAQBAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api','Christopher Paolini, Marie-Hélène DELVAL, Anne Delcourt','Bayard Jeunesse',NULL,'2023-11-08',NULL,912,'9782747059527','Il y a peu encore, Eragon n\'était qu\'un simple garçon de ferme, et Saphira, son dragon, une étrange pierre bleue ramassée dans la forêt... Depuis, le sort de plusieurs peuples repose sur leurs épaules. De longs mois d\'entraînement et de combats, s\'ils ont permis des victoires et ranimé l\'espoir, ont aussi provoqué des pertes cruelles. Or, l\'ultime bataille contre Galbatorix reste à mener. Certes, Eragon et Saphira ne sont pas seuls, ils ont des alliés : les Vardens conduits par Nasuada, Arya et les elfes, le roi Orik et ses nains, Garzhvog et ses redoutables Urgals. Le peuple des chats-garous s\'est même joint à eux avec son roi, Grimrr Demi-Patte. Pourtant, si le jeune Dragonnier et sa puissante compagne aux écailles bleues ne trouvent pas en eux-mêmes la force d\'abattre le tyran, personne n\'y réussira. Ils n\'auront pas de seconde chance. Tel est leur destin. Il faut renverser le roi maléfique, restaurer la paix et la justice en Alagaësia. Quel que soit le prix à payer.','uWiVDAAAQBAJ','2026-02-05 22:00:44'),(16,'Eragon poche, Tome 05','https://books.google.com/books/content?id=G8qLEQAAQBAJ&printsec=frontcover&img=1&zoom=1&source=gbs_api','Christopher Paolini, Marie-Hélène DELVAL, Éric Moreau, John Jude Palencar','Bayard Jeunesse',NULL,'2025-11-05',NULL,752,'9791036392115','<i>Murtagh</i> enfin en poche !<p>Un traître... Un dragon... Une nouvelle épopée... En Alagaësia, des mois se sont écoulés depuis la chute du tyran Galbatorix. Murtagh le Dragonnier et son dragon Thorn sont toujours considérés comme des traîtres et des meurtriers, car le royaume ignore l\'aide qu\'ils ont apportée à Eragon et Nasuada. Ils vivent en parias, à l\'abri des regards. Mais la rumeur d\'étranges évènements, aux confins de l\'Alagaësia, ravivent de douloureux souvenirs pour Murtagh et Thorn. Et nul ne peut se soustraire à son destin. Commence alors pour nos héros un voyage épique à travers des terres à la fois familières et inexplorées. Confrontés à des ennemis aussi terrifiants qu\'imprévisibles, ils auront besoin de courage et d\'espérance. Car une mystérieuse puissance oeuvre dans l\'ombre... 20 ans après la parution d\'Eragon, Christopher Paolini rouvre les portes de l\'Alagaësia dans un récit magistral sur Murtagh, l\'intriguant demi-frère d\'Eragon. Ce roman peut se lire indépendamment de la saga L\'héritage (mais rien ne vous empêche de lire les quatre tomes !)</p>Une quête de rédemption dans un monde de magie<p>Cette édition en poche du bestseller <i>Murtagh</i> offre une plongée magistrale dans l\'univers de la fantasy. Ce récit se distingue par son souffle narratif, sa richesse émotionnelle et son regard neuf sur un personnage longtemps resté dans l\'ombre. Entre magie, dragons et révélations, ce spin-off s\'adresse aux lecteurs dès 12 ans, qu\'ils aient lu ou non la saga <i>L\'Héritage</i>. </p>Explorer le courage, l\'exil et l\'espoir<p>Ce roman propose bien plus qu\'une aventure fantastique : il interroge la notion de choix, la quête d\'identité et la force du lien entre un homme et son dragon. Grâce à une traduction soignée de Marie-Hélène Delval et Éric Moreau, <i>Murtagh</i> offre une lecture fluide et captivante. Un incontournable pour les passionnés de fantasy.</p>','G8qLEQAAQBAJ','2026-02-05 22:00:47');
/*!40000 ALTER TABLE `book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection`
--

DROP TABLE IF EXISTS `collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `scope` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visibility` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `published_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `media_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_image` longtext COLLATE utf8mb4_unicode_ci,
  `genre` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FC4D6532A76ED395` (`user_id`),
  CONSTRAINT `FK_FC4D6532A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection`
--

LOCK TABLES `collection` WRITE;
/*!40000 ALTER TABLE `collection` DISABLE KEYS */;
INSERT INTO `collection` VALUES (16,8,'Non répertorié',NULL,'system','private',0,'2026-02-04 23:16:26',NULL,'all',NULL,NULL),(17,8,'Liste d\'envie',NULL,'system','private',0,'2026-02-04 23:16:26',NULL,'all',NULL,NULL),(19,8,'Studio Ghibli','Une envie de s\'évader ? découvre la collection des films d\'animations du studio Ghibli','user','public',1,'2026-02-04 23:16:26','2026-02-05 22:23:39','movie','https://image.tmdb.org/t/p/w500/39wmItIWsg5sZMyRUHLkWBcuVCM.jpg','Romance'),(20,9,'Non répertorié',NULL,'system','private',0,'2026-02-04 23:16:26',NULL,'all',NULL,NULL),(21,9,'Liste d\'envie',NULL,'system','private',0,'2026-02-04 23:16:26',NULL,'all',NULL,NULL),(24,10,'Non répertorié',NULL,'system','private',0,'2026-02-04 23:16:26',NULL,'all',NULL,NULL),(25,10,'Liste d\'envie',NULL,'system','private',0,'2026-02-04 23:16:26',NULL,'all',NULL,NULL),(27,10,'La collection stellaire !','Une collection publique de films sur l\'espace !','user','public',1,'2026-02-04 23:16:26','2026-01-25 23:16:26','movie','https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg','Science-Fiction'),(28,7,'Non répertorié',NULL,'system','private',0,'2026-02-04 23:25:55',NULL,'all',NULL,NULL),(29,7,'Liste d\'envie',NULL,'system','private',0,'2026-02-04 23:25:55',NULL,'all',NULL,NULL),(30,8,'La collection Eragon','Bien plus que des dragons !','user','public',1,'2026-02-05 22:02:17','2026-02-05 22:12:34','book','https://books.google.com/books/publisher/content?id=jWaVDAAAQBAJ&printsec=frontcover&img=1&zoom=1&edge=curl&imgtk=AFLRE71-3QRfkz8EwwkW7OEfWglTCve5XfjXdOhQqhplWiC1mTTCYNKX3KnUf-2CAvwT1kVGF0RkV6Tcdhj3tij-gH565bSk1HjAnkfksA7i4VRQ8iIZf_3CqM8GPQxjKjxbs-8B3Y-f&source=gbs_api','Fantasy'),(31,9,'La trilogie Equalizer','Denzel Washington au sommet de son art !','user','public',1,'2026-02-06 10:02:54','2026-02-06 10:25:12','movie','https://image.tmdb.org/t/p/w342/lgzPn7C9dWanHl2DjE97855dsJF.jpg','Action'),(32,9,'Toujours plus d\'action','De l\'action comme vous l\'avez jamais vu !','user','public',1,'2026-02-06 10:31:33','2026-02-06 10:32:22','movie','https://image.tmdb.org/t/p/w342/8gd71hpzHIF3gCkmJBwV5egtu3k.jpg','Action');
/*!40000 ALTER TABLE `collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_book`
--

DROP TABLE IF EXISTS `collection_book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_book` (
  `id` int NOT NULL AUTO_INCREMENT,
  `collection_id` int NOT NULL,
  `book_id` int NOT NULL,
  `added_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_collection_book` (`collection_id`,`book_id`),
  KEY `IDX_81928FDF514956FD` (`collection_id`),
  KEY `IDX_81928FDF16A2B381` (`book_id`),
  CONSTRAINT `FK_81928FDF16A2B381` FOREIGN KEY (`book_id`) REFERENCES `book` (`id`),
  CONSTRAINT `FK_81928FDF514956FD` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_book`
--

LOCK TABLES `collection_book` WRITE;
/*!40000 ALTER TABLE `collection_book` DISABLE KEYS */;
INSERT INTO `collection_book` VALUES (25,30,12,'2026-02-05 22:00:32'),(26,30,13,'2026-02-05 22:00:38'),(27,30,14,'2026-02-05 22:00:41'),(28,30,15,'2026-02-05 22:00:44'),(29,30,16,'2026-02-05 22:00:47');
/*!40000 ALTER TABLE `collection_book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_movie`
--

DROP TABLE IF EXISTS `collection_movie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_movie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `collection_id` int NOT NULL,
  `movie_id` int NOT NULL,
  `added_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_collection_movie` (`collection_id`,`movie_id`),
  KEY `IDX_5AA64A3C514956FD` (`collection_id`),
  KEY `IDX_5AA64A3C8F93B6FC` (`movie_id`),
  CONSTRAINT `FK_5AA64A3C514956FD` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`id`),
  CONSTRAINT `FK_5AA64A3C8F93B6FC` FOREIGN KEY (`movie_id`) REFERENCES `movie` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_movie`
--

LOCK TABLES `collection_movie` WRITE;
/*!40000 ALTER TABLE `collection_movie` DISABLE KEYS */;
INSERT INTO `collection_movie` VALUES (19,19,12,'2026-02-04 23:16:26'),(26,27,11,'2026-02-04 23:16:26'),(37,27,18,'2026-02-05 11:23:42'),(38,27,19,'2026-02-05 11:23:51'),(39,19,20,'2026-02-05 22:21:31'),(40,19,21,'2026-02-05 22:21:46'),(41,19,22,'2026-02-05 22:21:57'),(42,19,23,'2026-02-05 22:22:07'),(43,31,14,'2026-02-06 09:52:26'),(44,31,15,'2026-02-06 09:52:30'),(45,31,24,'2026-02-06 09:52:32'),(46,32,25,'2026-02-06 09:52:49'),(47,32,26,'2026-02-06 09:52:54'),(48,32,27,'2026-02-06 09:53:02'),(49,32,28,'2026-02-06 09:53:04');
/*!40000 ALTER TABLE `collection_movie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `collection_id` int NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `guest_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_email` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9474526CA76ED395` (`user_id`),
  KEY `IDX_9474526C514956FD` (`collection_id`),
  CONSTRAINT `FK_9474526C514956FD` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`id`),
  CONSTRAINT `FK_9474526CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment`
--

LOCK TABLES `comment` WRITE;
/*!40000 ALTER TABLE `comment` DISABLE KEYS */;
INSERT INTO `comment` VALUES (12,9,19,'Je valide, surtout pour les films. Ajoute-en d\'autres.','2026-02-04 23:16:26',NULL,NULL,NULL,NULL,NULL),(13,NULL,19,'Je découvre le site, c\'est propre et lisible.','2026-02-04 23:16:26',NULL,'Invité Demo','invite@exemple.local',NULL,NULL),(17,8,27,'Très bonne collection, j\'aime la sélection.','2026-02-04 23:16:26',NULL,NULL,NULL,NULL,NULL),(18,9,27,'Je valide, surtout pour les films. Ajoute-en d\'autres.','2026-02-04 23:16:26',NULL,NULL,NULL,NULL,NULL),(19,NULL,27,'Je découvre le site, c\'est propre et lisible.','2026-02-04 23:16:26',NULL,'Invité Demo','invite@exemple.local',NULL,NULL),(21,NULL,27,'Ce film est exceptionnel !','2026-02-05 14:14:36',NULL,'Murphy','murphy@mediatech.test','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:147.0) Gecko/20100101 Firefox/147.0');
/*!40000 ALTER TABLE `comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_message`
--

DROP TABLE IF EXISTS `contact_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_message` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_2C9211FEA76ED395` (`user_id`),
  CONSTRAINT `FK_2C9211FEA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_message`
--

LOCK TABLES `contact_message` WRITE;
/*!40000 ALTER TABLE `contact_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES ('DoctrineMigrations\\Version20260131102141','2026-01-31 10:21:55',28),('DoctrineMigrations\\Version20260131105427','2026-01-31 10:54:40',25),('DoctrineMigrations\\Version20260131111812','2026-01-31 11:18:29',44),('DoctrineMigrations\\Version20260131113910','2026-01-31 11:39:12',106),('DoctrineMigrations\\Version20260131115339','2026-01-31 11:53:40',94),('DoctrineMigrations\\Version20260131120008','2026-01-31 12:00:10',97),('DoctrineMigrations\\Version20260131121148','2026-01-31 12:11:49',69),('DoctrineMigrations\\Version20260131121708','2026-01-31 12:17:09',56),('DoctrineMigrations\\Version20260131122341','2026-01-31 12:23:42',70),('DoctrineMigrations\\Version20260131124206','2026-01-31 12:42:07',66),('DoctrineMigrations\\Version20260131125922','2026-01-31 12:59:24',62),('DoctrineMigrations\\Version20260202110204','2026-02-02 11:02:05',44),('DoctrineMigrations\\Version20260202152510','2026-02-02 15:25:23',43),('DoctrineMigrations\\Version20260202162744','2026-02-02 16:27:49',26),('DoctrineMigrations\\Version20260203090328','2026-02-03 09:03:40',51),('DoctrineMigrations\\Version20260203115506','2026-02-03 11:55:09',50),('DoctrineMigrations\\Version20260203163327','2026-02-03 16:33:28',41),('DoctrineMigrations\\Version20260204102000','2026-02-04 09:15:55',48),('DoctrineMigrations\\Version20260205113000','2026-02-05 11:32:58',65),('DoctrineMigrations\\Version20260205220744','2026-02-05 22:07:44',31),('DoctrineMigrations\\Version20260205220920','2026-02-05 22:09:21',27),('DoctrineMigrations\\Version20260205223732','2026-02-05 22:37:33',55);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genre`
--

DROP TABLE IF EXISTS `genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genre`
--

LOCK TABLES `genre` WRITE;
/*!40000 ALTER TABLE `genre` DISABLE KEYS */;
INSERT INTO `genre` VALUES (11,'Science-fiction','book'),(12,'Fantasy','book'),(13,'Thriller','book'),(14,'Histoire','book'),(15,'Développement personnel','book'),(16,'Action','movie'),(17,'Comédie','movie'),(18,'Drame','movie'),(19,'Science-fiction','movie'),(20,'Animation','movie');
/*!40000 ALTER TABLE `genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempt`
--

DROP TABLE IF EXISTS `login_attempt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempt` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `success` tinyint(1) NOT NULL,
  `attempted_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempt`
--

LOCK TABLES `login_attempt` WRITE;
/*!40000 ALTER TABLE `login_attempt` DISABLE KEYS */;
INSERT INTO `login_attempt` VALUES (10,'alice@mediatech.test','127.0.0.1',0,'2026-02-04 23:20:20'),(11,'admin@mediatech.test','127.0.0.1',0,'2026-02-04 23:23:29'),(12,'admin@mediatech.test','127.0.0.1',0,'2026-02-04 23:23:38'),(13,'admin@mediatech.local','127.0.0.1',1,'2026-02-04 23:25:42'),(14,'charlie@mediatech.local','127.0.0.1',1,'2026-02-05 08:41:52'),(15,'charlie@mediatech.local','127.0.0.1',1,'2026-02-05 09:56:32'),(16,'charlie@mediatech.local','127.0.0.1',1,'2026-02-05 10:22:15'),(17,'admin@mediatech.local','127.0.0.1',1,'2026-02-05 15:43:40'),(18,'admin@mediatech.local','127.0.0.1',1,'2026-02-05 15:45:34'),(19,'alice@mediatech.local','127.0.0.1',1,'2026-02-05 21:58:11'),(20,'alice@mediatech.local','127.0.0.1',1,'2026-02-06 09:18:36'),(21,'bob@mediatech.local','127.0.0.1',1,'2026-02-06 09:34:24'),(22,'bob@mediatech.local','127.0.0.1',1,'2026-02-06 09:51:41'),(23,'admin@mediatech.local','127.0.0.1',0,'2026-02-06 10:45:51'),(24,'admin@mediatech.local','127.0.0.1',0,'2026-02-06 14:57:06'),(25,'admin@mediatech.local','127.0.0.1',0,'2026-02-06 14:57:13'),(26,'admin@mediatech.local','127.0.0.1',0,'2026-02-06 14:57:33'),(27,'admin@mediatech.test','127.0.0.1',1,'2026-02-06 14:58:35'),(28,'admin@mediatech.test','127.0.0.1',1,'2026-02-06 15:16:05');
/*!40000 ALTER TABLE `login_attempt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_messages`
--

LOCK TABLES `messenger_messages` WRITE;
/*!40000 ALTER TABLE `messenger_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movie`
--

DROP TABLE IF EXISTS `movie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `poster` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `director` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_date` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `synopsis` longtext COLLATE utf8mb4_unicode_ci,
  `tmdb_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1D5EF26F55BCC5E5` (`tmdb_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movie`
--

LOCK TABLES `movie` WRITE;
/*!40000 ALTER TABLE `movie` DISABLE KEYS */;
INSERT INTO `movie` VALUES (11,'Interstellar','https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',NULL,'Science-fiction',NULL,'Une mission spatiale pour sauver l\'humanité.',157336,'2026-02-04 23:16:26'),(12,'Le Voyage de Chihiro','https://image.tmdb.org/t/p/w500/39wmItIWsg5sZMyRUHLkWBcuVCM.jpg',NULL,'Animation',NULL,'Une jeune fille se retrouve piégée dans un monde d\'esprits.',100002,'2026-02-04 23:16:26'),(13,'Mad Max: Fury Road','https://image.tmdb.org/t/p/w500/8tZYtuWezp8JbcsvHYO0O46tFbo.jpg',NULL,'Action',NULL,'Une course-poursuite dans un désert post-apocalyptique.',100003,'2026-02-04 23:16:26'),(14,'Equalizer','https://image.tmdb.org/t/p/w342/lgzPn7C9dWanHl2DjE97855dsJF.jpg',NULL,NULL,'2014-09-24','McCall, un homme qui pense avoir rangé son passé mystérieux derrière lui, se consacre à sa nouvelle vie tranquille. Au moment où il rencontre Teri, une jeune fille sous le contrôle de gangsters russes violents, il décide d\'agir. McCall sort ainsi de sa retraite et voit son désir de justice réveillé.',156022,'2026-02-05 10:23:17'),(15,'Equalizer 2','https://image.tmdb.org/t/p/w342/7WqXdXqWBwOO8LYOYxoD1RtIcNM.jpg',NULL,NULL,'2018-07-19','Robert McCall sert inlassablement la justice au nom des exploités et des opprimés. Mais jusqu’où est-il prêt à aller lorsque cela touche quelqu’un qu’il aime ?',345887,'2026-02-05 10:23:52'),(16,'Dune : Première partie','https://image.tmdb.org/t/p/w342/qpyaW4xUPeIiYA5ckg5zAZFHvsb.jpg',NULL,NULL,'2021-09-15','L\'histoire de Paul Atreides, jeune homme aussi doué que brillant, voué à connaître un destin hors du commun qui le dépasse totalement. Car, s\'il veut préserver l\'avenir de sa famille et de son peuple, il devra se rendre sur Dune, la planète la plus dangereuse de l\'Univers. Mais aussi la seule à même de fournir la ressource la plus précieuse capable de décupler la puissance de l\'Humanité. Tandis que des forces maléfiques se disputent le contrôle de cette planète, seuls ceux qui parviennent à dominer leur peur pourront survivre…',438631,'2026-02-05 10:31:53'),(17,'Matrix','https://image.tmdb.org/t/p/w342/pEoqbqtLc4CcwDUDqxmEDSWpWTZ.jpg',NULL,NULL,'1999-03-31','Programmeur anonyme dans un service administratif le jour, Thomas Anderson devient Neo la nuit venue. Sous ce pseudonyme, il est l\'un des pirates les plus recherchés du cyber‐espace. À cheval entre deux mondes, Neo est assailli par d\'étranges songes et des messages cryptés provenant d\'un certain Morpheus. Celui‐ci l\'exhorte à aller au‐delà des apparences et à trouver la réponse à la question qui hante constamment ses pensées : qu\'est‐ce que la Matrice ? Nul ne le sait, et aucun homme n\'est encore parvenu à en percer les défenses. Mais Morpheus est persuadé que Neo est l\'Élu, le libérateur mythique de l\'humanité annoncé selon la prophétie. Ensemble, ils se lancent dans une lutte sans retour contre la Matrice et ses terribles agents…',603,'2026-02-05 11:16:57'),(18,'Seul sur Mars','https://image.tmdb.org/t/p/w342/51CqiKVStZ2YPx64iPs3LDDiQnt.jpg',NULL,NULL,'2015-09-30','Au cours d’une mission spatiale habitée sur Mars, et à la suite d’un violent orage, l’astronaute Mark Watney est laissé pour mort et abandonné sur place par son équipage. Mais Watney a survécu et se retrouve seul sur cette planète hostile. Avec de maigres provisions, il ne doit compter que sur son ingéniosité, son bon sens et son intelligence pour survivre et trouver un moyen d’alerter la Terre qu’il est encore vivant. À des millions de kilomètres de là, la NASA et une équipe de scientifiques internationaux travaillent sans relâche pour ramener « le Martien » sur terre, pendant que, en parallèle, ses coéquipiers tentent secrètement d’organiser une audacieuse voire impossible mission de sauvetage.',286217,'2026-02-05 11:23:42'),(19,'Gravity','https://image.tmdb.org/t/p/w342/cPrUo65h0CDXsAB9t3yL6wfv6j1.jpg',NULL,NULL,'2013-10-03','Pour sa première expédition à bord d\'une navette spatiale, le docteur Ryan Stone, brillante experte en ingénierie médicale, accompagne l\'astronaute chevronné Matt Kowalski qui effectue son dernier vol avant de prendre sa retraite. Mais alors qu\'il s\'agit apparemment d\'une banale sortie dans l\'espace, une catastrophe se produit. Lorsque la navette est pulvérisée, Stone et Kowalski se retrouvent totalement seuls, livrés à eux-mêmes dans l\'univers...',49047,'2026-02-05 11:23:51'),(20,'Princesse Mononoké','https://image.tmdb.org/t/p/w342/1AfSDxBTYYtQRVY2V1ISgxXNPVx.jpg',NULL,NULL,'1997-07-12','Parti en quête d\'un remède, un prince affecté par un mal fatal se retrouve dans une contrée où se livre une bataille entre une ville minière et les animaux de la forêt.',128,'2026-02-05 22:21:31'),(21,'Le Château ambulant','https://image.tmdb.org/t/p/w342/45PVXJUYfH6yIINcQKelQ0SJPvh.jpg',NULL,NULL,'2004-09-09','La jeune Sophie, âgée de 18 ans, travaille sans relâche dans la boutique de chapelier que tenait son père avant de mourir. Lors de l’une de ses rares sorties en ville, elle fait la connaissance de Hauru le Magicien. Celui‐ci est extrêmement séduisant, mais n’a pas beaucoup de caractère… Se méprenant sur leur relation, une sorcière jette un épouvantable sort sur Sophie et la transforme en vieille femme de 90 ans. Accablée, Sophie s’enfuit et erre dans les terres désolées. Par hasard, elle pénètre dans le Château Ambulant de Hauru et, cachant sa véritable identité, s’y fait engager comme femme de ménage. Cette « vieille dame » aussi mystérieuse que dynamique va bientôt redonner une nouvelle vie à l’ancienne demeure. Plus énergique que jamais, Sophie accomplit des miracles. Quel fabuleux destin l’attend  ? Et si son histoire avec Hauru n’en était qu’à son véritable commencement  ?',4935,'2026-02-05 22:21:46'),(22,'Mon voisin Totoro','https://image.tmdb.org/t/p/w342/eEpy8IiR8N0S6mgkdAjDCMlMYQO.jpg',NULL,NULL,'1988-04-16','Mei, 4 ans, et Satsuki, 10 ans, s’installent à la campagne avec leur père pour se rapprocher de l’hôpital où séjourne leur mère. Elles découvrent la nature tout autour de la maison et, surtout, l’existence d’animaux étranges et merveilleux, les Totoros, avec qui elles deviennent très amies. Un jour, alors que Satsuki et Mei attendent le retour de leur mère, elles apprennent que sa sortie de l’hôpital a été repoussée. Mei décide alors d’aller lui rendre visite seule. Satsuki et les gens du village la recherchent en vain. Désespérée, Satsuki va finalement demander de l’aide à son voisin Totoro.',8392,'2026-02-05 22:21:57'),(23,'Nausicaä de la vallée du vent','https://image.tmdb.org/t/p/w342/sIcv6IiaL6Ad2KGUOdRyJHIZpgC.jpg',NULL,NULL,'1984-03-11','Dans un monde où la nature est hostile à l’Homme, Nausicaä – princesse de la Vallée du Vent – se bat pour protéger son village. Mais la guerre des hommes frappe à leur porte. Les vestiges d’une humanité industrielle menacent l’équilibre entre les rescapés humains du grand cataclysme et les insectes, protecteurs de la forêt toxique.',81,'2026-02-05 22:22:07'),(24,'Equalizer 3','https://image.tmdb.org/t/p/w342/go2gtucO5LT92jnU40pbD0LImgg.jpg',NULL,NULL,'2023-08-30','Depuis qu\'il a renoncé à sa vie d\'assassin au service du gouvernement, Robert McCall peine à enterrer les démons de son passé et trouve un étrange réconfort en défendant les opprimés. Alors qu\'il pense avoir trouvé un havre de paix dans le sud de l\'Italie, il découvre que ses amis sont sous le contrôle de la mafia locale. Quand les évènements prennent une tournure mortelle, McCall sait exactement ce qu\'il doit faire : protéger ses amis en s\'attaquant directement à la pègre.',926393,'2026-02-06 09:52:32'),(25,'John Wick','https://image.tmdb.org/t/p/w342/orB4dHfZ9rOJbKYow7W0etezchl.jpg',NULL,NULL,'2014-10-22','Depuis la mort de sa femme bien‐aimée, John Wick passe ses journées à retaper sa Ford Mustang de 1969, avec pour seule compagnie sa chienne Daisy. Il mène une vie sans histoire, jusqu’à ce qu’un malfrat sadique nommé Iosef Tarasof remarque sa voiture. John refuse de la lui vendre. Iosef n’acceptant pas qu’on lui résiste, s’introduit chez John avec deux complices pour voler la Mustang, et tuer sauvagement Daisy… John remonte la piste de Iosef jusqu’à New York. Un ancien contact, Aurelio, lui apprend que le malfrat est le fils unique d’un grand patron de la pègre, Viggo Tarasof. La rumeur se répand rapidement dans le milieu : le légendaire tueur cherche Iosef. Viggo met à prix la tête de John : quiconque l’abattra touchera une énorme récompense. John a désormais tous les assassins de New York aux trousses.',245891,'2026-02-06 09:52:49'),(26,'John Wick : Chapitre 2','https://image.tmdb.org/t/p/w342/r687UV1zQ5KDB9AxRokRscWIRvt.jpg',NULL,NULL,'2017-02-08','John Wick est forcé de sortir de sa retraite volontaire par un de ses ex-associés qui cherche à prendre le contrôle d’une mystérieuse confrérie de tueurs internationaux. Parce qu’il est lié à cet homme par un serment, John se rend à Rome, où il va devoir affronter certains des tueurs les plus dangereux du monde.',324552,'2026-02-06 09:52:54'),(27,'John Wick : Parabellum','https://image.tmdb.org/t/p/w342/8gd71hpzHIF3gCkmJBwV5egtu3k.jpg',NULL,NULL,'2019-05-15','John Wick a transgressé une règle fondamentale : il a tué à l’intérieur même de l’Hôtel Continental. \"Excommunié\", tous les services liés au Continental lui sont fermés et sa tête mise à prix. John se retrouve sans soutien, traqué par tous les plus dangereux tueurs du monde.',458156,'2026-02-06 09:53:02'),(28,'John Wick : Chapitre 4','https://image.tmdb.org/t/p/w342/n1YTIyhAqqqFyDGFTzV7WaU1JfK.jpg',NULL,NULL,'2023-03-22','John Wick affronte ses adversaires les plus redoutables dans ce quatrième volet de la série.  De New York à Osaka, en passant par Paris et Berlin, John Wick mène un combat contre la Grande Table, la terrible organisation criminelle qui a mis sa tête à prix, en affrontant ses tueurs les plus dangereux...',603692,'2026-02-06 09:53:04');
/*!40000 ALTER TABLE `movie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rating` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `collection_id` int NOT NULL,
  `value` smallint NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_rating_user_collection` (`user_id`,`collection_id`),
  KEY `IDX_D8892622A76ED395` (`user_id`),
  KEY `IDX_D8892622514956FD` (`collection_id`),
  CONSTRAINT `FK_D8892622514956FD` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D8892622A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rating`
--

LOCK TABLES `rating` WRITE;
/*!40000 ALTER TABLE `rating` DISABLE KEYS */;
INSERT INTO `rating` VALUES (7,8,19,5,'2026-02-04 23:16:26',NULL),(8,9,19,4,'2026-02-04 23:16:26',NULL),(11,8,27,5,'2026-02-04 23:16:26','2026-02-05 22:43:54'),(12,9,27,4,'2026-02-04 23:16:26',NULL);
/*!40000 ALTER TABLE `rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reset_password_request`
--

DROP TABLE IF EXISTS `reset_password_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_password_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `selector` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hashed_token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `expires_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_7CE748AA76ED395` (`user_id`),
  CONSTRAINT `FK_7CE748AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_password_request`
--

LOCK TABLES `reset_password_request` WRITE;
/*!40000 ALTER TABLE `reset_password_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `reset_password_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pseudo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `biography` longtext COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  UNIQUE KEY `UNIQ_8D93D64986CC499D` (`pseudo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (7,'admin@mediatech.test','[\"ROLE_ADMIN\"]','$2y$13$rhFUXChIitgYVH9mvETmy.O1aQ/vHTWIShIG461U7CGGyafnZOxcG','admin',NULL,'Je suis le chef ici',1,'2026-02-04 23:16:24'),(8,'alice@mediatech.local','[]','$2y$13$BJvquKbB26294wqXkM7uxuEL.JGuglmh5csxlXSdC8lRk.2TlVQDW','alice',NULL,'Je crée des collections publiques et je teste les commentaires.',1,'2026-02-04 23:16:24'),(9,'bob@mediatech.local','[]','$2y$13$igWC.AtBAcBARduHxWw3weq20A8jDOB..KJyFivBuSUYbmXIfaWky','bob','uploads/profiles/potichat-6985c2a2ae804.jpg','Je teste les notes et les listes d\'envie.',1,'2026-02-04 23:16:25'),(10,'charlie@mediatech.local','[]','$2y$13$CCHs0sOrfDsQQJ3AM3rnV.FfyDGQzwh9l2tfPX/AjECuEeJ5GR2iu','charlie','uploads/profiles/smartcat-698472df83a40.jpg','Passionnée par les films de science-fiction depuis tout petit !',1,'2026-02-04 23:16:25');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_favorite_genre`
--

DROP TABLE IF EXISTS `user_favorite_genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_favorite_genre` (
  `user_id` int NOT NULL,
  `genre_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`genre_id`),
  KEY `IDX_5E5DC785A76ED395` (`user_id`),
  KEY `IDX_5E5DC7854296D31F` (`genre_id`),
  CONSTRAINT `FK_5E5DC7854296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_5E5DC785A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_favorite_genre`
--

LOCK TABLES `user_favorite_genre` WRITE;
/*!40000 ALTER TABLE `user_favorite_genre` DISABLE KEYS */;
INSERT INTO `user_favorite_genre` VALUES (8,12),(8,20),(9,13),(9,16);
/*!40000 ALTER TABLE `user_favorite_genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'mediatech'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-06 16:52:43
