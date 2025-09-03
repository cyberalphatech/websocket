package demo;

import rk.netDevice.sdk.p2.*;

import java.io.IOException;
import java.text.SimpleDateFormat;


public class Demo {

	public static void main(String[] args) throws IOException,
			InterruptedException {
		RSServer rsServer = RSServer.Initiate(2404);// ��ʼ��
		rsServer.addDataListener(new IDataListener() {// ��Ӽ���
			@Override
			public void receiveTimmingAck(TimmingAck data) {// Уʱָ��Ӧ����
				System.out.println("УʱӦ��->�豸���:" + data.getDeviceId()
						+ "\tִ�н����" + data.getStatus());
			}

			@Override
			public void receiveTelecontrolAck(TelecontrolAck data) {// ң��ָ��Ӧ����
				System.out.println("ң��Ӧ��->�豸���:" + data.getDeviceId()
						+ "\t�̵������:" + data.getRelayId() + "\tִ�н��:"
						+ data.getStatus());
			}

			@Override
			public void receiveStoreData(StoreData data) {// �Ѵ洢���ݽ��մ���
				// �����ڵ����ݡ����ݰ��������豸�������Լ������ڵ����ݡ���ʪ�����ݴ���ڽڵ�������
				for (NodeData nd : data.getNodeList()) {
					SimpleDateFormat sdf = new SimpleDateFormat(
							"yy-MM-dd HH:mm:ss");
					String str = sdf.format(nd.getRecordTime());
					System.out.println("�洢����->�豸��ַ:" + data.getDeviceId()
							+ "\t�ڵ�:" + nd.getNodeId() + "\t�¶�:" + nd.getTem()
							+ "\tʪ��:" + nd.getHum() + "\t�洢ʱ��:" + str);
				}

			}

			@Override
			public void receiveRealtimeData(RealTimeData data) {// ʵʱ���ݽ��մ���
				// �����ڵ����ݡ����ݰ��������豸�������Լ������ڵ����ݡ���ʪ�����ݴ���ڽڵ�������
				for (NodeData nd : data.getNodeList()) {
					System.out.println("ʵʱ����->�豸��ַ:" + data.getDeviceId()
							+ "\t�ڵ�:" + nd.getNodeId() + "\t�¶�:" + nd.getTem()
							+ "\tʪ��:" + nd.getHum() + "\t����:" + data.getLng()
							+ "\tγ��:" + data.getLat() + "\t��������:"
							+ data.getCoordinateType() + "\t�̵���״̬:"
							+ data.getRelayStatus());
				}

			}

			@Override
			public void receiveLoginData(LoginData data) {// ��¼���ݽ��մ���
				System.out.println("��¼->�豸��ַ:" + data.getDeviceId());

			}

			@Override
			public void receiveParamIds(ParamIdsData data) {
				String str = "�豸��������б�->�豸��ţ�" + data.getDeviceId()
						+ "\t������������" + data.getTotalCount() + "\t��֡����������"
						+ data.getCount() + "\r\n";
				for (int paramId : data.getPararmIdList())// �����豸�в���id���
				{
					str += paramId + ",";
				}
				System.out.println(str);

			}

			@Override
			public void receiveParam(ParamData data) {
				String str = "�豸����->�豸��ţ�" + data.getDeviceId() + "\r\n";

				for (ParamItem pararm : data.getParameterList()) {
					str += "������ţ�"
							+ pararm.getParamId()
							+ "\t����������"
							+ pararm.getDescription()
							+ "\t����ֵ��"
							+ (pararm.getValueDescription() == null ? pararm
									.getValue() : pararm.getValueDescription()
									.get(pararm.getValue())) + "\r\n";
				}
				System.out.println(str);

			}

			@Override
			public void receiveWriteParamAck(WriteParamAck data) {
				String str = "�����豸����->�豸��ţ�" + data.getDeviceId() + "\t����������"
						+ data.getCount() + "\t"
						+ (data.isSuccess() ? "���سɹ�" : "����ʧ��");
				System.out.println(str);

			}

			@Override
			public void receiveTransDataAck(TransDataAck data) {
				String str = "����͸��->�豸��ţ�" + data.getDeviceId() + "\t��Ӧ�����"
						+ data.getData() + "\r\n�ֽ�����" + data.getTransDataLen();
				System.out.println(str);

			}

			@Override
			public void receiveHeartbeatData(HeartbeatData heartbeatData) {

			}
		});
		rsServer.start();

	}

}
