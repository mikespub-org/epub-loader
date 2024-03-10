CREATE TABLE users (
                            id INTEGER PRIMARY KEY,
                            name TEXT NOT NULL,
                            pw TEXT NOT NULL,
                            timestamp TEXT DEFAULT CURRENT_TIMESTAMP,
                            session_data TEXT NOT NULL DEFAULT "{}",
                            restriction TEXT NOT NULL DEFAULT "{}",
                            readonly TEXT NOT NULL DEFAULT "n",
                            misc_data TEXT NOT NULL DEFAULT "{}",
                            UNIQUE(name)
                        );

PRAGMA user_version=1;
