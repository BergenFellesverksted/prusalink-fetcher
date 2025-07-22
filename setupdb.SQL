CREATE TABLE 3dprinter_status (
  ip                  VARCHAR(45)     NOT NULL,
  printer_name        VARCHAR(100)    NOT NULL,
  -- job info
  job_id              INT             NULL,
  job_progress        DECIMAL(5,2)    NULL,
  time_remaining      INT             NULL,
  time_printing       INT             NULL,
  -- storage info
  storage_path        VARCHAR(255)    NULL,
  storage_name        VARCHAR(100)    NULL,
  storage_read_only   TINYINT(1)      NULL,
  -- printer info
  state               VARCHAR(50)     NOT NULL,
  temp_bed            DECIMAL(5,2)    NOT NULL,
  target_bed          DECIMAL(5,2)    NOT NULL,
  temp_nozzle         DECIMAL(5,2)    NOT NULL,
  target_nozzle       DECIMAL(5,2)    NOT NULL,
  axis_z              DECIMAL(6,2)    NOT NULL,
  axis_x              DECIMAL(6,2)    NULL,
  axis_y              DECIMAL(6,2)    NULL,
  flow                INT             NOT NULL,
  speed               INT             NOT NULL,
  fan_hotend          INT             NOT NULL,
  fan_print           INT             NOT NULL,
  -- auto-updating timestamp
  last_updatedUTC     TIMESTAMP       NOT NULL
                              DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ip)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4;
