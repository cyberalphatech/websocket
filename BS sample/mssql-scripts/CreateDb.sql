USE [master]
/****** Object:  Database [attdb]    Script Date: 03/21/2015 20:40:58 ******/
IF  EXISTS (SELECT name FROM sys.databases WHERE name = N'attdb')
DROP DATABASE [AttDB]
GO

/****** Object:  Database [AttDB]    Script Date: 12/21/2013 11:19:05 ******/
CREATE DATABASE [AttDB] ON  PRIMARY 
( NAME = N'AttDB', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL.1\MSSQL\Data\AttDB.mdf' , SIZE = 3072KB , MAXSIZE = UNLIMITED, FILEGROWTH = 1024KB )
 LOG ON 
( NAME = N'AttDB_log', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL.1\MSSQL\Data\AttDB_log.ldf' , SIZE = 1024KB , MAXSIZE = 2048GB , FILEGROWTH = 10%)
GO

IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [AttDB].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO

ALTER DATABASE [AttDB] SET ANSI_NULL_DEFAULT OFF 
GO

ALTER DATABASE [AttDB] SET ANSI_NULLS OFF 
GO

ALTER DATABASE [AttDB] SET ANSI_PADDING OFF 
GO

ALTER DATABASE [AttDB] SET ANSI_WARNINGS OFF 
GO

ALTER DATABASE [AttDB] SET ARITHABORT OFF 
GO

ALTER DATABASE [AttDB] SET AUTO_CLOSE OFF 
GO

ALTER DATABASE [AttDB] SET AUTO_CREATE_STATISTICS ON 
GO

ALTER DATABASE [AttDB] SET AUTO_SHRINK OFF 
GO

ALTER DATABASE [AttDB] SET AUTO_UPDATE_STATISTICS ON 
GO

ALTER DATABASE [AttDB] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO

ALTER DATABASE [AttDB] SET CURSOR_DEFAULT  GLOBAL 
GO

ALTER DATABASE [AttDB] SET CONCAT_NULL_YIELDS_NULL OFF 
GO

ALTER DATABASE [AttDB] SET NUMERIC_ROUNDABORT OFF 
GO

ALTER DATABASE [AttDB] SET QUOTED_IDENTIFIER OFF 
GO

ALTER DATABASE [AttDB] SET RECURSIVE_TRIGGERS OFF 
GO

ALTER DATABASE [AttDB] SET  DISABLE_BROKER 
GO

ALTER DATABASE [AttDB] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO

ALTER DATABASE [AttDB] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO

ALTER DATABASE [AttDB] SET TRUSTWORTHY OFF 
GO

ALTER DATABASE [AttDB] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO

ALTER DATABASE [AttDB] SET PARAMETERIZATION SIMPLE 
GO

ALTER DATABASE [AttDB] SET READ_COMMITTED_SNAPSHOT OFF 
GO


ALTER DATABASE [AttDB] SET  READ_WRITE 
GO

ALTER DATABASE [AttDB] SET RECOVERY SIMPLE 
GO

ALTER DATABASE [AttDB] SET  MULTI_USER 
GO

ALTER DATABASE [AttDB] SET PAGE_VERIFY CHECKSUM  
GO

ALTER DATABASE [AttDB] SET DB_CHAINING OFF 
GO

/*********************************************************************************************************/
USE [AttDB]
GO
/****** Object:  Table [dbo].[tbl_fkcmd_trans]    Script Date: 02/08/2013 04:44:04 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_fkcmd_trans]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_fkcmd_trans]
GO

/****** Object:  Table [dbo].[tbl_fkcmd_trans]    Script Date: 12/08/2012 11:38:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
-- 이 표는 지령의 수행상태를 추적하기 위한 표이다.
CREATE TABLE [dbo].[tbl_fkcmd_trans](
	[trans_id] [varchar](16) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[device_id] [varchar](24) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[cmd_code] [varchar](32) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[return_code] [varchar](64) COLLATE Chinese_PRC_CI_AS NULL,
	[status] [varchar](16) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[update_time] [datetime] NOT NULL,
 CONSTRAINT [PK_tbl_fkcmd_trans] PRIMARY KEY NONCLUSTERED 
(
	[trans_id] ASC
)WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO

/*********************************************************************************************************/
USE [AttDB]
GO
/****** Object:  Table [dbo].[tbl_fkcmd_trans_cmd_param]    Script Date: 02/08/2013 04:44:04 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_fkcmd_trans_cmd_param]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_fkcmd_trans_cmd_param]
GO

/****** Object:  Table [dbo].[tbl_fkcmd_trans_cmd_param]    Script Date: 12/08/2012 11:38:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
-- 이 표는 지령의 입구파라메터자료를 보관하기 위한 표이다.
CREATE TABLE [dbo].[tbl_fkcmd_trans_cmd_param](
	[trans_id] [varchar](16) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[device_id] [varchar](24) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[cmd_param] [varbinary](max) NULL,
CONSTRAINT [PK_tbl_fkcmd_trans_cmd_param] PRIMARY KEY NONCLUSTERED 
(
	[trans_id] ASC
)WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO

/*********************************************************************************************************/
USE [AttDB]
GO
/****** Object:  Table [dbo].[tbl_fkcmd_trans_cmd_result]    Script Date: 02/08/2013 04:44:04 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_fkcmd_trans_cmd_result]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_fkcmd_trans_cmd_result]
GO

/****** Object:  Table [dbo].[tbl_fkcmd_trans_cmd_param]    Script Date: 12/08/2012 11:38:13 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
-- 이 표는 지령의 결과자료를 보관하기 위한 표이다.
CREATE TABLE [dbo].[tbl_fkcmd_trans_cmd_result](
	[trans_id] [varchar](16) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[device_id] [varchar](24) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[cmd_result] [varbinary](max) NULL,
CONSTRAINT [PK_tbl_fkcmd_trans_cmd_result] PRIMARY KEY NONCLUSTERED 
(
	[trans_id] ASC
)WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO


/*********************************************************************************************************/
USE [AttDB]
GO

/****** Object:  Table [dbo].[tbl_fkdevice_status]    Script Date: 02/08/2013 04:45:30 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_fkdevice_status]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_fkdevice_status]
GO

/****** Object:  Table [dbo].[tbl_fkdevice_status]    Script Date: 12/08/2012 11:38:33 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
-- 이 표는 기대의 련결상태를 보여주는 표이다.
CREATE TABLE [dbo].[tbl_fkdevice_status](
	[device_id] [varchar](24) NOT NULL,
	[device_name] [varchar](24) NOT NULL,
	[connected] [int] NOT NULL,
	[last_update_time] [datetime] NOT NULL,
	[last_update_fk_time] [datetime] NULL,
	[device_info] [nvarchar](2048) NULL,
 CONSTRAINT [PK_tbl_fkdevice_status] PRIMARY KEY NONCLUSTERED 
(
	[device_id] ASC
)WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO

/*********************************************************************************************************/
USE [AttDB]
GO

/****** Object:  Table [dbo].[tbl_realtime_glog]    Script Date: 02/08/2013 04:45:46 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_realtime_glog]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_realtime_glog]
GO

/****** Object:  Table [dbo].[tbl_realtime_glog]    Script Date: 12/08/2012 11:39:10 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[tbl_realtime_glog](
	[update_time] [datetime] NOT NULL,
	[device_id] [varchar](24) COLLATE Chinese_PRC_CI_AS NULL,
	[user_id] [varchar](24) COLLATE Chinese_PRC_CI_AS NULL,
	[verify_mode] [varchar](64) COLLATE Chinese_PRC_CI_AS NULL,
	[io_mode] [varchar](32) COLLATE Chinese_PRC_CI_AS NULL,
	[io_time] [datetime] NULL,
	[log_image] [varchar](max) NULL
) ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO

/*********************************************************************************************************/
USE [AttDB]
GO

/****** Object:  Table [dbo].[tbl_realtime_enroll_data]    Script Date: 02/08/2013 04:45:59 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_realtime_enroll_data]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_realtime_enroll_data]
GO

/****** Object:  Table [dbo].[tbl_realtime_enroll_data]    Script Date: 12/08/2012 11:38:52 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[tbl_realtime_enroll_data](
	[update_time] [datetime] NOT NULL,
	[device_id] [varchar](24) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[user_id] [varchar](24) COLLATE Chinese_PRC_CI_AS NOT NULL,
	[user_data] [varbinary](max) NULL
) ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO
----------------------------------------------------------------------------------------------------------------------------------------
-- tbl_fkcmd_trans_cmd_result_log_data
--
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_fkcmd_trans_cmd_result_log_data]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_fkcmd_trans_cmd_result_log_data]

GO

CREATE TABLE [dbo].[tbl_fkcmd_trans_cmd_result_log_data](
	[trans_id] [nvarchar](16) NOT NULL,
	[device_id] [nvarchar](24) NOT NULL,
	[user_id] [nvarchar](16) NULL,
	[verify_mode] [nvarchar](64) NULL,
	[io_mode] [nvarchar](32) NULL,
	[io_time] [datetime] NULL
) ON [PRIMARY]

GO

----------------------------------------------------------------------------------------------------------------------------------------
-- tbl_fkcmd_trans_cmd_result_user_id_list
--
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbl_fkcmd_trans_cmd_result_user_id_list]') AND type in (N'U'))
DROP TABLE [dbo].[tbl_fkcmd_trans_cmd_result_log_data]

GO

CREATE TABLE [dbo].[tbl_fkcmd_trans_cmd_result_user_id_list](
	[trans_id] [nvarchar](16) NOT NULL,
	[device_id] [nvarchar](24) NOT NULL,
	[user_id] [nvarchar](16) NULL,
	[backup_number] [int] NULL
) ON [PRIMARY]

GO

USE [AttDB]
GO

/****** Object:  StoredProcedure [dbo].[usp_check_reset_fk_cmd]    Script Date: 12/21/2013 17:31:55 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[usp_check_reset_fk_cmd]') AND type in (N'P', N'PC'))
DROP PROCEDURE [dbo].[usp_check_reset_fk_cmd]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:	PEFIS, 리일현
-- Create date: 2013-12-21
-- Description:	'기대재기동'지령이 발행된것이 있으면 그것을 기대로 내려보낸다.
--  출퇴근기를 재기동하면 현재 수행중의 모든 지령들이 없어지므로 자료기지의 표에서도 이러한 내용이 반영되여야 한다.
--  해당 출퇴근기에 대하여 지령수행상태가 'RUN'인 지령들은 모두 상태를 'CANCELLED'로 바꾼다.
--  지령수행상태가 'RUN'인 지령들은 big_field테이블에
--   지령수행과정에 주고 받은 자료의 잔해가 남아있을수 있다. 따라서 이러한 것들도 함께 지워버린다.
-- =============================================
CREATE PROCEDURE usp_check_reset_fk_cmd
	-- Add the parameters for the stored procedure here
	@dev_id varchar(24),
	@trans_id varchar(16) output
AS
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

    -- trans_id를 무효한 값으로 설정한다.
    select @trans_id=''
    if @dev_id is null or len(@dev_id) = 0
		return -1
    
    -- Insert statements for procedure here
	-- 해당 기대에 대하여 '기대재기동'지령이 발행된것이 있는가 조사한다.
	SELECT @trans_id=trans_id FROM tbl_fkcmd_trans where device_id=@dev_id AND cmd_code='RESET_FK' AND status='WAIT'
	if @@ROWCOUNT = 0
		return -2 -- 없다면 복귀한다.
	
	begin transaction
	BEGIN TRY
		declare @trans_id_tmp as varchar(16)
		declare @csrTransId as cursor
		set @csrTransId = Cursor For
			 select trans_id
			 from tbl_fkcmd_trans
			 where device_id=@dev_id AND status='RUN'

		-- 기대의 지령수행상태가 'RUN'인것들을 조사하여 그에 해당한 레코드들을
		--  tbl_fkcmd_trans_cmd_param 표와 tbl_fkcmd_trans_cmd_result 표에서 지운다.
		Open @csrTransId
		Fetch Next From @csrTransId	Into @trans_id_tmp
		While(@@FETCH_STATUS = 0)
		begin
			DELETE FROM tbl_fkcmd_trans_cmd_param WHERE trans_id=@trans_id_tmp
			DELETE FROM tbl_fkcmd_trans_cmd_result WHERE trans_id=@trans_id_tmp
			Fetch Next From @csrTransId	Into @trans_id_tmp
		end
		close @csrTransId
		
		-- 기대의 지령수행상태가 'RUN'인것들을 'CANCELLED'로 바꾼다.
		UPDATE tbl_fkcmd_trans SET status='CANCELLED', update_time = GETDATE() WHERE device_id=@dev_id AND status='RUN'
		-- 기대에 대해 '재기동'지령이 발행된것들이 또 있으면 그것들의 상태를 'RESULT'로 바꾼다.
		UPDATE tbl_fkcmd_trans SET status='RESULT', update_time = GETDATE() WHERE device_id=@dev_id AND cmd_code='RESET_FK'
	END TRY
    BEGIN CATCH
		rollback transaction
		select @trans_id=''
		return -2
    END CATCH

	commit transaction
	return 0
END -- proc: usp_check_reset_fk_cmd
GO

/****** Object:  StoredProcedure [dbo].[usp_update_device_conn_status]    Script Date: 12/21/2013 17:36:20 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[usp_update_device_conn_status]') AND type in (N'P', N'PC'))
DROP PROCEDURE [dbo].[usp_update_device_conn_status]
GO

-- =============================================
-- Author:	PEFIS, 리일현
-- Create date: 2013-12-21
-- Description:	기대의 접속상태표를 갱신한다.
--   기대는 일정한 시간간격으로 자기에게 발해된 지령이 있는가를 문의한다. 이때 기대의 접속상태표를 갱신한다.
--   이때 기대는 기대시간과 기대의 펌웨어는 무엇인가 등과 같은 정보를 함께 올려 보낸다.
-- =============================================
CREATE PROCEDURE [dbo].[usp_update_device_conn_status]
	-- Add the parameters for the stored procedure here
	@dev_id varchar(24),
	@dev_name varchar(24),
	@tm_last_update datetime,
	@fktm_last_update datetime,
	@dev_info varchar(256)
AS
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

    -- Insert statements for procedure here
	declare @dev_registered int
	if len(@dev_id) < 1 
		return -1
	if len(@dev_name) < 1 
		return -1
	
	begin transaction
	
	SELECT @dev_registered = COUNT(device_id) from tbl_fkdevice_status WHERE device_id=@dev_id
	if  @dev_registered = 0
	begin
		INSERT INTO tbl_fkdevice_status( 
				device_id, 
				device_name, 
				connected, 
				last_update_time, 
				last_update_fk_time, 
				device_info)
			VALUES(
				@dev_id,
				@dev_name, 
				1,
				@tm_last_update,
				@fktm_last_update,
				@dev_info)
	end	
	else -- if @@ROWCOUNT = 0
	begin
		UPDATE tbl_fkdevice_status SET 
				device_id=@dev_id, 
				device_name=@dev_name, 
				connected=1,
				last_update_time=@tm_last_update,
				last_update_fk_time=@fktm_last_update,
				device_info=@dev_info
			WHERE 
				device_id=@dev_id
	end
	
	if @@error <> 0
	begin
		rollback transaction
		return -2
	end
	
	commit transaction
	return 0
END -- proc: usp_update_device_conn_status

GO

/****** Object:  StoredProcedure [dbo].[usp_receive_cmd]    Script Date: 12/24/2013 16:55:20 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[usp_receive_cmd]') AND type in (N'P', N'PC'))
DROP PROCEDURE [dbo].[usp_receive_cmd]
GO

-- =============================================
-- Author:	PEFIS, 리일현
-- Create date: 2013-12-24
-- Modified date: 2014-12-4
-- Description:	기대가 자기에게로 발행한 지령을 얻어낼때 호출된다.
--  tbl_fkcmd_trans표에서 지령수행상태가 'WAIT'로 되여 있는것들 가운데서 가장 시간이 오래된것을 얻어낸 다음 상태를 'RUN'으로 바꾼다.
--  일부 지령들에 대해서(SET_ENROLL_DATA, SET_USER_INO)의 파라메터들은
--   tbl_fkcmd_trans_cmd_param 표에 존재하게 된다.
--
--  만일 어떤 기대에 대해서 발행된 새 지령을 얻는 시점에서
--   tbl_fkcmd_trans표에 이 기대로 발행된 지령들중 상태가 'RUN'인 지령들이 존재하면 그 지령들의 상태를 'CANCELLED'로 바꾼다.
--  기대가 지령을 받아 처리하고 결과를 올려보내던 중 어떤 문제로 하여 결과를 올려보내지 못하여 이러한 기록들이 생길수 있다.
--  이러한 지령들은 상태가 'RUN'에서 더 바뀔 가능성이 없으므로 상태를 'CANCELLED'로 바꾼다.
-- 또한 상태를 'RUN'으로부터 'CANCELLED'로 바꿀때 tbl_fkcmd_trans_cmd_param, tbl_fkcmd_trans_cmd_result 표들에 남아있는 잔해들을 지운다.
-- =============================================
CREATE PROCEDURE [dbo].[usp_receive_cmd]
	-- Add the parameters for the stored procedure here
	@dev_id varchar(24),
	@trans_id varchar(16) output,
	@cmd_code varchar(32) output,
	@cmd_param_bin varbinary(max) output
AS
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

    select @trans_id = ''
	-- 파라메터들을 검사한다.
	if @dev_id is null or len(@dev_id) = 0
		return -1
	
	begin transaction
	BEGIN TRY
		declare @trans_id_tmp as varchar(16)
		declare @csrTransId as cursor
		
		-- 먼저 tbl_fkcmd_trans 표에서 실행상태가 'RUN'인것들의 trans_id를 얻어낸다.
		set @csrTransId = Cursor For
			 select trans_id
			 from tbl_fkcmd_trans
			 where device_id=@dev_id AND status='RUN'
		
		-- tbl_fkcmd_trans_cmd_param, tbl_fkcmd_trans_cmd_result 표들에서 
		--  해당 trans_id에 해당한 레코드들을 삭제한다.
		Open @csrTransId
		Fetch Next From @csrTransId	Into @trans_id_tmp
		While(@@FETCH_STATUS = 0)
		begin
			DELETE FROM tbl_fkcmd_trans_cmd_param WHERE trans_id=@trans_id_tmp
			DELETE FROM tbl_fkcmd_trans_cmd_result WHERE trans_id=@trans_id_tmp
			Fetch Next From @csrTransId	Into @trans_id_tmp
		end
		close @csrTransId
	END TRY
    BEGIN CATCH
		rollback transaction
		select @trans_id=''
		return -2
    END CATCH
	
	-- tbl_fkcmd_trans 표에서 실행상태가 'RUN' 이던 트랜잭션들의 상태를 'CANCELLED'로 바꾼다.
	UPDATE tbl_fkcmd_trans SET status='CANCELLED', update_time = GETDATE() WHERE device_id=@dev_id AND status='RUN'
	if @@error <> 0
	begin
		rollback transaction
		return -2
	end
	commit transaction
	
		BEGIN TRY
		SELECT @trans_id=trans_id, @cmd_code=cmd_code FROM tbl_fkcmd_trans
		WHERE device_id=@dev_id AND status='WAIT' ORDER BY update_time DESC
		
		if @@ROWCOUNT = 0
		begin
			select @trans_id=''
			return -3
		end
		
		--  tbl_fkcmd_trans_cmd_param 표의 cmd_param 필드의 값을 출구파라메터 @cmd_param_bin에 설정한다.
		select @cmd_param_bin=cmd_param from tbl_fkcmd_trans_cmd_param
		where trans_id=@trans_id
		
		--  tbl_fkcmd_trans 표의 status 필드의 값을 'WAIT'로 바꾼다.
		UPDATE tbl_fkcmd_trans SET status='RUN', update_time = GETDATE() WHERE trans_id=@trans_id
	END TRY
    BEGIN CATCH
    	select @trans_id=''
		return -2
	END CATCH

	return 0
END -- proc: usp_receive_cmd
GO

/****** Object:  StoredProcedure [dbo].[usp_set_cmd_result]    Script Date: 12/24/2013 16:55:20 ******/
IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[usp_set_cmd_result]') AND type in (N'P', N'PC'))
DROP PROCEDURE [dbo].[usp_set_cmd_result]
GO

-- =============================================
-- Author:	PEFIS, 리일현
-- Create date: 2014-12-5
-- Description:	기대가 지령수행결과를 올려보낼때 호출된다.
--  지령의 수행결과 얻어진 자료를 tbl_fkcmd_trans_cmd_result 표에 보관한다.
--  tbl_fkcmd_trans 표에서 trans_id 에 해당한 지령의 수행상태가 'RUN'로 되여 있는 경우
--   지령의 결과코드를 보관하고 그 지령의 상태를 'RESULT'로 바꾼다.
-- =============================================
CREATE PROCEDURE [dbo].[usp_set_cmd_result]
	-- Add the parameters for the stored procedure here
	@dev_id varchar(24),
	@trans_id varchar(16),
	@return_code varchar(128),
	@cmd_result_bin varbinary(max)
AS
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

	-- 파라메터들을 검사한다.
	if @dev_id is null or len(@dev_id) = 0
		return -1
	if @trans_id is null or len(@trans_id) = 0
		return -1
	
	begin transaction
	BEGIN TRY
		select trans_id from tbl_fkcmd_trans where trans_id = @trans_id and status='RUN'
		if @@ROWCOUNT != 1
		begin
			return -2
		end
		
		-- 먼저 tbl_fkcmd_trans_cmd_result 표에서 @trans_id에 해당한 레코드를 지우고 결과자료를 삽입한다.
		-- 만일 바이너리결과자료의 길이가 0이면 레코드를 삽입하지 않는다.
		delete from tbl_fkcmd_trans_cmd_result where trans_id=@trans_id
		if len(@cmd_result_bin) > 0
		begin
			insert into tbl_fkcmd_trans_cmd_result (trans_id, device_id, cmd_result) values(@trans_id, @dev_id, @cmd_result_bin)
		end
		
		-- tbl_fkcmd_trans 표에서 실행상태가 'RUN' 이던 트랜잭션들의 상태를 'RESULT'로 바꾼다.
		update tbl_fkcmd_trans set status='RESULT', return_code=@return_code, update_time = GETDATE() where trans_id=@trans_id and device_id=@dev_id and status='RUN'
	END TRY
    BEGIN CATCH
		rollback transaction
		return -3
    END CATCH
	
	if @@error <> 0
	begin
		rollback transaction
		return -3
	end
	commit transaction
	
	return 0
END -- proc: usp_set_cmd_result

