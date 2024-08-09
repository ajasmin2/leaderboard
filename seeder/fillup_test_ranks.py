import os
import uuid
import random
import redis
import mysql.connector
from mysql.connector import Error

REDIS_CONNECTION = None
DB_CONNECTION = None
PLAYERS_LIMIT = int(os.environ.get("PLAYERS_LIMIT", 10))
CHUNKS_MAX_SIZE = int(os.environ.get("CHUNKS_MAX_SIZE", 1000))
TEST_LEADERBOARD_NAME = os.environ.get("TEST_LEADERBOARD_NAME", "Roaring Rapids II")
LEDERBOARD_ID = int(os.environ.get("LLEADERBOARD_ID", 1))


class MySQLSingleton:
    _instance = None

    def __new__(cls):
        if not cls._instance:
            cls._instance = super(MySQLSingleton, cls).__new__(cls)
            cls._instance._initialize_connection()
        return cls._instance

    def _initialize_connection(self):
        try:
            self.connection = mysql.connector.connect(
                host=os.environ.get("DB_HOST", None),
                database=os.environ.get("DB_NAME", None),
                user=os.environ.get("DB_USER", None),
                password=os.environ.get("DB_HPASSWORD", None)
            )
            if self.connection.is_connected():
                print("Connected to MySQL database")
        except Error as e:
            print(f"Error while connecting to MySQL: {e}")

    def get_connection(self):
        return self.connection

class RedisSingleton:
    _instance = None

    def __new__(cls):
        if not cls._instance:
            cls._instance = super(RedisSingleton, cls).__new__(cls)
            cls._instance._initialize_connection()
        return cls._instance

    def _initialize_connection(self):
        self.connection = redis.StrictRedis(
        host=os.environ.get("REDIS_HOST", None),
        port=os.environ.get("REDIS_PORT", None),
        password=os.environ.get("REDIS_PASSWORD", None),
        db=os.environ.get("REDIS_DB", 0)
    )

    def get_connection(self):
        return self.connection

def insert_data_to_redis(players: dict, redis_obj: RedisSingleton):
    r = redis_obj.get_connection()
    r.zadd(f"ranking_leaderboard_{LEDERBOARD_ID}", players)


def insert_data_into_db(players: list, db_obj: MySQLSingleton):
    db = db_obj.get_connection()

    cursor = db.cursor()

    sql_insert_query = """
    INSERT INTO players_leaderboards (leaderboard_id, player_id, score, created_at, updated_at)
    VALUES (%s, %s, %s, NOW(), NOW())
    """
    cursor.executemany(sql_insert_query, players)
    db.commit()

def create_leaderboard(db_obj: MySQLSingleton):
    db = db_obj.get_connection()
    cursor = db.cursor()

    sql_insert_query = """
    INSERT INTO leaderboard (id, name, status, created_at, updated_at)
    VALUES (%s, %s, %s, NOW(), NOW())
    ON DUPLICATE KEY UPDATE status = 1, name = %s
    """

    cursor.execute(sql_insert_query, (1, TEST_LEADERBOARD_NAME, 1, TEST_LEADERBOARD_NAME))
    db.commit()

def insert_players(start_index: int, limit_index: int, redis_obj: RedisSingleton, db_obj: MySQLSingleton):
    players_list = []
    players_dict = {}
    while start_index < limit_index:
        start_index +=1
        username =  f"Player_{start_index}_{str(uuid.uuid4())[:8]}"
        score = random.randrange(1000000)
        score = score if score > 0 else 1
        players_list.append((LEDERBOARD_ID, username,score))
        players_dict.update({username:score})
    
    insert_data_into_db(players=players_list, db_obj=db_obj)
    insert_data_to_redis(players=players_dict, redis_obj=redis_obj)

def insert_data_into_leaderboard():
    redis_obj = RedisSingleton()
    db_obj = MySQLSingleton()
    create_leaderboard(db_obj=db_obj)

    offset = 0
    limit = 0
    
    while offset < PLAYERS_LIMIT:
        limit = CHUNKS_MAX_SIZE + offset
        if limit > PLAYERS_LIMIT:
            limit = PLAYERS_LIMIT

        insert_players(start_index=offset, limit_index=limit, redis_obj=redis_obj, db_obj=db_obj)
        print(f"Players from {offset + 1} to {limit} Done...")
        offset = limit

if __name__ == "__main__":
    print(f"Total players to insert: {PLAYERS_LIMIT}")
    insert_data_into_leaderboard()
    print(f"Seeder process completed!")
        
        






