package demo;

import rk.netDevice.sdk.p2.*;

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.ItemEvent;
import java.awt.event.ItemListener;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;

import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.SwingUtilities;

import javax.swing.GroupLayout;
import javax.swing.GroupLayout.Alignment;
import javax.swing.JPanel;
import javax.swing.border.LineBorder;

import java.awt.Color;

import javax.swing.border.TitledBorder;
import javax.swing.LayoutStyle.ComponentPlacement;

public class SwingDemo extends JFrame {

	/**
	 * 
	 */
	private static final long serialVersionUID = -7855826301914463533L;
	private JTextField txtPort;
	private JScrollPane scrollPane;
	private JTextArea textArea;
	private JButton btnStart;
	private JButton btnStop;
	private JCheckBox chkRelay0;
	private JCheckBox chkRelay1;
	private JCheckBox chkRelay2;
	private JCheckBox chkRelay3;
	private JCheckBox chkRelay4;
	private JCheckBox chkRelay5;
	private JCheckBox chkRelay6;
	private JCheckBox chkRelay7;
	private JButton btnTimming;
	private JButton btnCallStore;
	private RSServer rsServer;// 定义监听服务对象
	private IDataListener listener = new IDataListener() {

		@Override
		public void receiveTimmingAck(TimmingAck data) {// 校时指令应答处理
			textArea.append("校时应答->设备编号:" + data.getDeviceId() + "\t执行结果："
					+ data.getStatus() + "\r\n");
		}

		@Override
		public void receiveTelecontrolAck(TelecontrolAck data) {// 遥控指令应答处理
			textArea.append("遥控应答->设备编号:" + data.getDeviceId() + "\t继电器编号:"
					+ data.getRelayId() + "\t执行结果:" + data.getStatus() + "\r\n");
		}

		@Override
		public void receiveStoreData(StoreData data) {// 已存储数据接收处理
			// 遍历节点数据。数据包括网络设备的数据以及各个节点数据。温湿度数据存放在节点数据中
			for (NodeData nd : data.getNodeList()) {
				SimpleDateFormat sdf = new SimpleDateFormat("yy-MM-dd HH:mm:ss");
				String str = sdf.format(nd.getRecordTime());

				textArea.append("存储数据->设备地址:" + data.getDeviceId() + "\t节点:"
						+ nd.getNodeId() + "\t温度:" + nd.getTem() + "\t湿度:"
						+ nd.getHum() + "\t存储时间:" + str+"\t坐标类型："+nd.getCoordinateType()+"\t经度:"+nd.getLng()+"\t纬度："+nd.getLat() + "\r\n");

				
				
			}

		}

		@Override
		public void receiveRealtimeData(RealTimeData data) {// 实时数据接收处理
			// 遍历节点数据。数据包括网络设备的数据以及各个节点数据。温湿度数据存放在节点数据中
			SimpleDateFormat sdf = new SimpleDateFormat("yy-MM-dd HH:mm:ss");
			String time = sdf.format(new Date());
			for (NodeData nd : data.getNodeList()) {
				textArea.append(time+"\t实时数据->设备地址:" + data.getDeviceId() + "\t节点:"
						+ nd.getNodeId() + "\t温度:" + nd.getTem() + "\t湿度:"
						+ nd.getHum() + "\t经度：" + data.getLng() + "\t纬度："
						+ data.getLat() + "\t坐标类型：" + data.getCoordinateType()
						+ "\t继电器状态" + data.getRelayStatus() + "\t浮点型数据："
						+ nd.getFloatValue() + "\t32位有符号数据："
						+ nd.getSignedInt32Value() + "\t32位无符号数据："
						+ nd.getUnSignedInt32Value() + "\r\n");
			}

		}

		@Override
		public void receiveLoginData(LoginData data) {// 登录数据接收处理
			textArea.append("登录->设备地址:" + data.getDeviceId() + "\r\n");

		}

		@Override
		public void receiveParamIds(ParamIdsData data) {
			String str = "设备参数编号列表->设备编号：" + data.getDeviceId() + "\t参数总数量："
					+ data.getTotalCount() + "\t本帧参数数量：" + data.getCount()
					+ "\r\n";
			for (int paramId : data.getPararmIdList())// 遍历设备中参数id编号
			{
				str += paramId + ",";
			}
			textArea.append(str + "\r\n");

		}

		@Override
		public void receiveParam(ParamData data) {
			String str = "设备参数->设备编号：" + data.getDeviceId() + "\r\n";

			for (ParamItem pararm : data.getParameterList()) {
				str += "参数编号："
						+ pararm.getParamId()
						+ "\t参数描述："
						+ pararm.getDescription()
						+ "\t参数值："
						+ (pararm.getValueDescription() == null ? pararm
								.getValue() : pararm.getValueDescription().get(
								pararm.getValue())) + "\r\n";
			}
			textArea.append(str + "\r\n");

		}

		@Override
		public void receiveWriteParamAck(WriteParamAck data) {
			String str = "下载设备参数->设备编号：" + data.getDeviceId() + "\t参数数量："
					+ data.getCount() + "\t"
					+ (data.isSuccess() ? "下载成功" : "下载失败");
			textArea.append(str + "\r\n");

		}

		@Override
		public void receiveTransDataAck(TransDataAck data) {
			String str = "数据透传->设备编号：" + data.getDeviceId() + "\t响应结果："
					+ data.getData() + "\r\n字节数：" + data.getTransDataLen();
			textArea.append(str + "\r\n");

		}

		@Override
		public void receiveHeartbeatData(HeartbeatData heartbeatData) {

		}
	};

	private JTextField txtDeviceId;
	private JTextField txtParamIds;
	private JTextField txtParamId;
	private JTextField txtParamVal;
	private JPanel panel_2;
	private JLabel label_4;
	private JTextField txtTransData;
	private JButton btnTrans;

	public SwingDemo() {
		setTitle("Demo");
		setResizable(false);
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
		setSize(653, 710);
		setLocationRelativeTo(null);

		JLabel lblNewLabel = new JLabel("\u7AEF\u53E3:");
		lblNewLabel.setBounds(10, 10, 40, 15);

		txtPort = new JTextField();
		txtPort.setBounds(45, 7, 66, 21);
		txtPort.setText("2404");
		txtPort.setColumns(10);

		btnStart = new JButton("\u542F\u52A8");
		btnStart.setBounds(135, 6, 85, 23);
		btnStart.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent arg0) {

				btnStart.setEnabled(false);
				new Thread(new Runnable() {

					@Override
					public void run() {

						rsServer = RSServer.Initiate(Integer.parseInt(txtPort
								.getText()),"C:/param.dat");// 初始化

						rsServer.addDataListener(listener);// 添加数据监听事件
						try {
							rsServer.start();
						} catch (InterruptedException e) {
							// TODO Auto-generated catch block
							e.printStackTrace();
						}// 启动监听服务
					}
				}).start();
			}

		});

		btnStop = new JButton("\u505C\u6B62");
		btnStop.setBounds(237, 6, 85, 23);
		btnStop.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent arg0) {
				btnStart.setEnabled(true);
				rsServer.stop();
			}
		});

		scrollPane = new JScrollPane();
		scrollPane.setBounds(10, 400, 624, 275);

		textArea = new JTextArea();
		scrollPane.setViewportView(textArea);

		JLabel label = new JLabel("\u8BBE\u5907\u5730\u5740:");
		label.setBounds(10, 48, 66, 15);

		txtDeviceId = new JTextField();
		txtDeviceId.setBounds(75, 45, 84, 21);
		txtDeviceId.setText("10000000");
		txtDeviceId.setColumns(10);

		btnTimming = new JButton("\u6821\u65F6");
		btnTimming.setBounds(336, 6, 85, 23);
		btnTimming.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent arg0) {
				int deviceId = Integer.parseInt(txtDeviceId.getText());
				rsServer.timming(deviceId);
			}
		});

		btnCallStore = new JButton("\u53EC\u5524\u6570\u636E");
		btnCallStore.setBounds(428, 6, 90, 23);
		btnCallStore.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				int deviceId = Integer.parseInt(txtDeviceId.getText());

				rsServer.callStoreData(deviceId);
			}
		});

		JPanel panel = new JPanel();
		panel.setBounds(10, 84, 624, 57);
		panel.setBorder(new TitledBorder(null,
				"\u7EE7\u7535\u5668\u63A7\u5236", TitledBorder.LEADING,
				TitledBorder.TOP, null, null));

		JPanel panel_1 = new JPanel();
		panel_1.setBounds(10, 147, 624, 112);
		panel_1.setBorder(new TitledBorder(null, "\u8BBE\u5907\u53C2\u6570",
				TitledBorder.LEADING, TitledBorder.TOP, null, null));

		panel_2 = new JPanel();
		panel_2.setBounds(10, 269, 624, 113);
		panel_2.setBorder(new TitledBorder(null, "\u6570\u636E\u900F\u4F20",
				TitledBorder.LEADING, TitledBorder.TOP, null, null));
		panel_2.setLayout(null);

		label_4 = new JLabel(
				"\u900F\u4F20\u6570\u636E\uFF0C16\u8FDB\u5236\u5B57\u7B26\u4E32");
		label_4.setBounds(10, 23, 419, 15);
		panel_2.add(label_4);

		txtTransData = new JTextField();
		txtTransData.setBounds(10, 48, 604, 21);
		panel_2.add(txtTransData);
		txtTransData.setColumns(10);

		btnTrans = new JButton("\u6570\u636E\u900F\u4F20");
		btnTrans.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent arg0) {
				int deviceId = Integer.parseInt(txtDeviceId.getText());

				rsServer.trans(deviceId, txtTransData.getText());
			}
		});
		btnTrans.setBounds(10, 79, 93, 23);
		panel_2.add(btnTrans);
		panel_1.setLayout(null);

		JLabel label_1 = new JLabel(
				"\u53C2\u6570\u7F16\u53F7\uFF0C\u7528\u4E8E\u8BFB\u53D6\u8BBE\u5907\u53C2\u6570\uFF08\u591A\u4E2A\u7F16\u53F7\u7528\u82F1\u6587,\u5206\u9694\uFF09");
		label_1.setBounds(10, 22, 421, 15);
		panel_1.add(label_1);

		txtParamIds = new JTextField();
		txtParamIds.setText("1,2,3,4,5,6,7,8,9,10");
		txtParamIds.setBounds(10, 47, 421, 21);
		panel_1.add(txtParamIds);
		txtParamIds.setColumns(10);

		JLabel label_2 = new JLabel("\u53C2\u6570\u7F16\u53F7");
		label_2.setBounds(10, 78, 54, 15);
		panel_1.add(label_2);

		txtParamId = new JTextField();
		txtParamId.setBounds(68, 75, 66, 21);
		panel_1.add(txtParamId);
		txtParamId.setColumns(10);

		JLabel label_3 = new JLabel("\u53C2\u6570\u503C");
		label_3.setBounds(144, 78, 54, 15);
		panel_1.add(label_3);

		txtParamVal = new JTextField();
		txtParamVal.setBounds(202, 75, 66, 21);
		panel_1.add(txtParamVal);
		txtParamVal.setColumns(10);

		JButton btnReadParametersList = new JButton(
				"\u8BFB\u53D6\u8BBE\u5907\u53C2\u6570\u5217\u8868");
		btnReadParametersList.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent arg0) {
				int deviceId = Integer.parseInt(txtDeviceId.getText());
				rsServer.callParamList(deviceId);// 发送召唤设备参数列表指令
			}
		});
		btnReadParametersList.setBounds(460, 18, 142, 23);
		panel_1.add(btnReadParametersList);

		JButton btnReadParameters = new JButton(
				"\u8BFB\u53D6\u8BBE\u5907\u53C2\u6570");
		btnReadParameters.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent arg0) {
				int deviceId = Integer.parseInt(txtDeviceId.getText());
				List<Integer> ids = new ArrayList<Integer>();
				String[] idArray = txtParamIds.getText().split(",");
				for (String str : idArray) {
					try {
						ids.add(Integer.parseInt(str));
					} catch (Exception e) {
					}
				}
				if (ids.size() >= 115) {

					JOptionPane.showMessageDialog(null, "一次读取参数数量不能超过115个",
							"提示", JOptionPane.INFORMATION_MESSAGE);
					return;
				}
				rsServer.callParam(deviceId, ids);

			}
		});
		btnReadParameters.setBounds(460, 46, 142, 23);
		panel_1.add(btnReadParameters);

		JButton btnWriteParameters = new JButton(
				"\u4E0B\u8F7D\u8BBE\u5907\u53C2\u6570");
		btnWriteParameters.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent arg0) {

				int deviceId = Integer.parseInt(txtDeviceId.getText());
				List<ParamItem> parameters = new ArrayList<ParamItem>();

				try {

					parameters.add(ParamItem.New(
							Integer.parseInt(txtParamId.getText()),
							txtParamVal.getText()));
				} catch (Exception ex) {
					JOptionPane.showMessageDialog(null, ex.getMessage(), "提示",
							JOptionPane.INFORMATION_MESSAGE);
					return;
				}
				if (parameters.size() > 115) {

					JOptionPane.showMessageDialog(null, "一次性下发参数数量不能超过115个",
							"提示", JOptionPane.INFORMATION_MESSAGE);
					return;
				}
				rsServer.writeParam(deviceId, parameters);
			}
		});
		btnWriteParameters.setBounds(460, 74, 142, 23);
		panel_1.add(btnWriteParameters);

		chkRelay0 = new JCheckBox("\u7EE7\u7535\u56680");
		panel.add(chkRelay0);

		chkRelay1 = new JCheckBox("\u7EE7\u7535\u56681");
		panel.add(chkRelay1);

		chkRelay2 = new JCheckBox("\u7EE7\u7535\u56682");
		panel.add(chkRelay2);

		chkRelay3 = new JCheckBox("\u7EE7\u7535\u56683");
		panel.add(chkRelay3);

		chkRelay4 = new JCheckBox("\u7EE7\u7535\u56684");
		panel.add(chkRelay4);

		chkRelay5 = new JCheckBox("\u7EE7\u7535\u56685");
		panel.add(chkRelay5);

		chkRelay6 = new JCheckBox("\u7EE7\u7535\u56686");
		panel.add(chkRelay6);

		chkRelay7 = new JCheckBox("\u7EE7\u7535\u56687");
		panel.add(chkRelay7);
		chkRelay7.addItemListener(new ChkItemListener(7));
		chkRelay6.addItemListener(new ChkItemListener(6));
		chkRelay5.addItemListener(new ChkItemListener(5));
		chkRelay4.addItemListener(new ChkItemListener(4));
		chkRelay3.addItemListener(new ChkItemListener(3));
		chkRelay2.addItemListener(new ChkItemListener(2));
		chkRelay1.addItemListener(new ChkItemListener(1));
		chkRelay0.addItemListener(new ChkItemListener(0));
		getContentPane().setLayout(null);
		getContentPane().add(txtPort);
		getContentPane().add(lblNewLabel);
		getContentPane().add(btnStart);
		getContentPane().add(btnStop);
		getContentPane().add(btnTimming);
		getContentPane().add(btnCallStore);
		getContentPane().add(txtDeviceId);
		getContentPane().add(label);
		getContentPane().add(panel_1);
		getContentPane().add(panel);
		getContentPane().add(panel_2);
		getContentPane().add(scrollPane);
	}

	class ChkItemListener implements ItemListener {

		private int relayId = 0;

		public ChkItemListener(int relayId) {
			this.relayId = relayId;
		}

		@Override
		public void itemStateChanged(ItemEvent e) {
			JCheckBox jcb = (JCheckBox) e.getItem();
			int deviceId = Integer.parseInt(txtDeviceId.getText());
			if (jcb.isSelected()) {

				try {
					rsServer.telecontrol(deviceId, relayId, 0, 0);
				} catch (Exception e1) {
					e1.printStackTrace();
				}

			} else {
				try {
					rsServer.telecontrol(deviceId, relayId, 1, 0);
				} catch (Exception e1) {
					e1.printStackTrace();
				}
			}
		}
	}

	public static void main(String[] args) {
		new SwingDemo().setVisible(true);

	}
}
